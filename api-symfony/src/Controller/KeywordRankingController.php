<?php

namespace App\Controller;

use App\Entity\Keyword;
use App\Entity\KeywordRanking;
use App\Repository\AuditRepository;
use App\Repository\KeywordRepository;
use App\Repository\KeywordRankingRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class KeywordRankingController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SiteRepository $siteRepository,
        private AuditRepository $auditRepository,
        private KeywordRepository $keywordRepository,
        private KeywordRankingRepository $keywordRankingRepository
    ) {
    }

    // =========================================================
    // NORMALIZE URL
    // Ki-homogénise l'URL باش match ma-يتوقفch على
    // trailing slash / www / http vs https
    // =========================================================

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        // Ajoute un scheme par défaut si absent (ex: "example.com")
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        $parts = parse_url($url);

        if (!$parts || empty($parts['host'])) {
            return strtolower(rtrim($url, '/'));
        }

        $host = strtolower($parts['host']);
        $host = preg_replace('/^www\./i', '', $host);

        $path = $parts['path'] ?? '';
        $path = rtrim($path, '/');

        return $host . $path;
    }

    // =========================================================
    // CREATE RANKING
    // POST /api/keyword-ranking
    // =========================================================

    #[Route(
        '/api/keyword-ranking',
        name: 'keyword_ranking',
        methods: ['POST']
    )]
    public function index(
        Request $request,
        #[CurrentUser] $user
    ): JsonResponse {

        // -----------------------------------------------------
        // Vérification JWT
        // -----------------------------------------------------

        if (!$user) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Utilisateur non authentifié.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // -----------------------------------------------------
        // JSON
        // -----------------------------------------------------

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid JSON.'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (
            empty($data['site_url']) ||
            empty($data['keyword'])
        ) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'site_url and keyword are required.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $siteUrl = trim($data['site_url']);
        $keywordValue = trim($data['keyword']);
        $normalizedInputUrl = $this->normalizeUrl($siteUrl);

        // -----------------------------------------------------
        // SITE + SECURITY
        // On récupère tous les sites de l'utilisateur puis on
        // compare les URLs normalisées en PHP (évite les faux
        // négatifs à cause de http/https, www., trailing slash...)
        // -----------------------------------------------------

        $userSites = $this->siteRepository
            ->createQueryBuilder('s')
            ->where('s.account = :account')
            ->setParameter('account', $user)
            ->getQuery()
            ->getResult();

        $site = null;

        foreach ($userSites as $candidate) {
            if ($this->normalizeUrl($candidate->getUrl()) === $normalizedInputUrl) {
                $site = $candidate;
                break;
            }
        }

        // -----------------------------------------------------
        // Site introuvable ou pas appartenant au user
        // -----------------------------------------------------

        if (!$site) {

            return new JsonResponse([
                'status' => 'error',
                'message' => 'Vous n’avez pas accès à ce site.'
            ], Response::HTTP_FORBIDDEN);
        }

        // -----------------------------------------------------
        // Dernier audit du site
        // -----------------------------------------------------

        $audit = $this->auditRepository->findOneBy(
            ['site' => $site],
            ['createdAt' => 'DESC']
        );

        if (!$audit) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'No audit found for this site. Run an audit first.'
            ], Response::HTTP_NOT_FOUND);
        }

        // -----------------------------------------------------
        // Appel Python Analyzer
        // -----------------------------------------------------

        $client = HttpClient::create();

        try {

            $response = $client->request(
                'GET',
                'http://analyzer:8000/serp/get-ranking',
                [
                    'query' => [
                        'keyword' => $keywordValue,
                        'site_url' => $site->getUrl(),
                    ]
                ]
            );

            $pythonResult = $response->toArray();

        } catch (\Throwable $e) {

            return new JsonResponse([
                'status' => 'error',
                'message' => 'Python service unavailable.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // -----------------------------------------------------
        // Python status
        // -----------------------------------------------------

        $status = $pythonResult['status'] ?? null;

        // -----------------------------------------------------
        // Site non trouvé dans Google
        // -----------------------------------------------------

        if ($status === 'not_found') {

            return new JsonResponse([
                'status' => 'not_found',
                'message' => 'Le site n’est pas classé pour ce mot-clé.'
            ], Response::HTTP_OK);
        }

        // -----------------------------------------------------
        // Python error
        // -----------------------------------------------------

        if ($status !== 'success') {

            return new JsonResponse(
                $pythonResult,
                Response::HTTP_OK
            );
        }

        // -----------------------------------------------------
        // Vérifier les données retournées par Python
        // -----------------------------------------------------

        if (
            !array_key_exists('position', $pythonResult) ||
            !array_key_exists('search_page', $pythonResult)
        ) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Python response is missing ranking data.',
                'data' => $pythonResult
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // -----------------------------------------------------
        // Keyword
        // -----------------------------------------------------

        $keyword = $this->keywordRepository->findOneBy([
            'keyword' => $keywordValue,
            'site' => $site
        ]);

        if (!$keyword) {

            $keyword = new Keyword();

            $keyword
                ->setKeyword($keywordValue)
                ->setSite($site)
                ->setCreatedAt(new \DateTimeImmutable());

            $this->em->persist($keyword);
        }

        // -----------------------------------------------------
        // Ranking
        // -----------------------------------------------------

        $ranking = new KeywordRanking();

        $ranking
            ->setKeyword($keyword)
            ->setAudit($audit)
            ->setSearchEngine('google')
            ->setDevice('desktop')
            ->setPosition(
                isset($pythonResult['position'])
                    ? (int) $pythonResult['position']
                    : null
            )
            ->setSearchPage(
                isset($pythonResult['search_page'])
                    ? (int) $pythonResult['search_page']
                    : null
            )
            ->setCheckedAt(new \DateTimeImmutable());

        $this->em->persist($ranking);

        $this->em->flush();

        // -----------------------------------------------------
        // Response
        // -----------------------------------------------------

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Keyword ranking saved successfully.',
            'data' => [
                'id' => $ranking->getId(),
                'keyword' => $keyword->getKeyword(),
                'site' => $site->getUrl(),
                'position' => $ranking->getPosition(),
                'search_page' => $ranking->getSearchPage(),
                'search_engine' => $ranking->getSearchEngine(),
                'device' => $ranking->getDevice(),
                'checked_at' => $ranking
                    ->getCheckedAt()
                    ->format('Y-m-d H:i:s'),
            ]
        ], Response::HTTP_CREATED);
    }


    // =========================================================
    // HISTORY
    // GET /api/keyword-ranking/history
    // =========================================================

    #[Route(
        '/api/keyword-ranking/history',
        name: 'keyword_ranking_history',
        methods: ['GET']
    )]
    public function history(
        #[CurrentUser] $user
    ): JsonResponse {

        // -----------------------------------------------------
        // JWT
        // -----------------------------------------------------

        if (!$user) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Utilisateur non authentifié.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // -----------------------------------------------------
        // Rankings du user connecté
        // -----------------------------------------------------

        $rankings = $this->keywordRankingRepository
            ->createQueryBuilder('kr')
            ->join('kr.keyword', 'k')
            ->join('k.site', 's')
            ->where('s.account = :user')
            ->setParameter('user', $user)
            ->orderBy('kr.checkedAt', 'DESC')
            ->getQuery()
            ->getResult();

        // -----------------------------------------------------
        // Format response
        // -----------------------------------------------------

        $history = [];

        foreach ($rankings as $ranking) {

            $keyword = $ranking->getKeyword();
            $site = $keyword?->getSite();

            $history[] = [
                'id' => $ranking->getId(),
                'keyword' => $keyword?->getKeyword(),
                'site' => $site?->getUrl(),
                'position' => $ranking->getPosition(),
                'search_page' => $ranking->getSearchPage(),
                'search_engine' => $ranking->getSearchEngine(),
                'device' => $ranking->getDevice(),
                'checked_at' => $ranking
                    ->getCheckedAt()
                    ?->format('Y-m-d H:i:s'),
            ];
        }

        // -----------------------------------------------------
        // Response
        // -----------------------------------------------------

        return new JsonResponse([
            'status' => 'success',
            'count' => count($history),
            'data' => $history
        ], Response::HTTP_OK);
    }


    // =========================================================
    // DELETE RANKING
    // DELETE /api/keyword-ranking/{id}
    // =========================================================

    #[Route(
        '/api/keyword-ranking/{id}',
        name: 'keyword_ranking_delete',
        methods: ['DELETE']
    )]
    public function delete(
        int $id,
        #[CurrentUser] $user
    ): JsonResponse {

        // -----------------------------------------------------
        // JWT
        // -----------------------------------------------------

        if (!$user) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Utilisateur non authentifié.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // -----------------------------------------------------
        // Ranking
        // -----------------------------------------------------

        $ranking = $this->keywordRankingRepository->find($id);

        if (!$ranking) {

            return new JsonResponse([
                'status' => 'error',
                'message' => 'Keyword ranking not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        // -----------------------------------------------------
        // Get Keyword + Site
        // -----------------------------------------------------

        $keyword = $ranking->getKeyword();
        $site = $keyword?->getSite();

        if (!$site) {

            return new JsonResponse([
                'status' => 'error',
                'message' => 'Site associated with ranking not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        // -----------------------------------------------------
        // SECURITY
        // Vérification avec ID
        // -----------------------------------------------------

        $siteAccount = $site->getAccount();

        if (
            !$siteAccount ||
            $siteAccount->getId() !== $user->getId()
        ) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Vous n’avez pas le droit de supprimer ce classement.'
            ], Response::HTTP_FORBIDDEN);
        }

        // -----------------------------------------------------
        // Delete
        // -----------------------------------------------------

        $this->em->remove($ranking);
        $this->em->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Keyword ranking deleted successfully.'
        ], Response::HTTP_OK);
    }
}