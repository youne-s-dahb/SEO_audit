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
    // =========================================================

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

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
    // FIND SITE FOR CURRENT USER
    // =========================================================

    private function findUserSite(
        string $siteUrl,
        $user
    ): ?object {
        $normalizedInput = $this->normalizeUrl($siteUrl);

        $sites = $this->siteRepository->findAll();

        foreach ($sites as $site) {
            $account = $site->getAccount();

            if (!$account) {
                continue;
            }

            if ($account->getId() !== $user->getId()) {
                continue;
            }

            if (
                $this->normalizeUrl($site->getUrl())
                === $normalizedInput
            ) {
                return $site;
            }
        }

        return null;
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
        // JWT
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

        $data = json_decode(
            $request->getContent(),
            true
        );

        if (!is_array($data)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid JSON.'
            ], Response::HTTP_BAD_REQUEST);
        }

        // -----------------------------------------------------
        // VALIDATION
        // -----------------------------------------------------

        $siteUrl = trim(
            (string) ($data['site_url'] ?? '')
        );

        $keywordValue = trim(
            (string) ($data['keyword'] ?? '')
        );

        if (
            $siteUrl === '' ||
            $keywordValue === ''
        ) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'site_url and keyword are required.'
            ], Response::HTTP_BAD_REQUEST);
        }

        // -----------------------------------------------------
        // SITE
        // -----------------------------------------------------

        $site = $this->findUserSite(
            $siteUrl,
            $user
        );

        if (!$site) {
            return new JsonResponse([
                'status' => 'error',
                'message' =>
                    'Site introuvable ou vous n’avez pas accès à ce site.'
            ], Response::HTTP_NOT_FOUND);
        }

        // -----------------------------------------------------
        // DERNIER AUDIT
        // -----------------------------------------------------

        $audit = $this->auditRepository->findOneBy(
            [
                'site' => $site
            ],
            [
                'createdAt' => 'DESC'
            ]
        );

        if (!$audit) {
            return new JsonResponse([
                'status' => 'error',
                'message' =>
                    'Aucun audit trouvé pour ce site. Lancez d’abord un audit.'
            ], Response::HTTP_NOT_FOUND);
        }

        // -----------------------------------------------------
        // PYTHON ANALYZER
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

            $pythonResult = $response->toArray(false);

        } catch (\Throwable $e) {

            return new JsonResponse([
                'status' => 'error',
                'message' =>
                    'Python service unavailable.',
                'details' =>
                    $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // -----------------------------------------------------
        // PYTHON STATUS
        // -----------------------------------------------------

        $status =
            $pythonResult['status'] ?? null;

        // -----------------------------------------------------
        // NOT FOUND
        // -----------------------------------------------------

        if ($status === 'not_found') {

            return new JsonResponse([
                'status' => 'not_found',
                'message' =>
                    'Le site n’est pas classé pour ce mot-clé.',
                'data' => [
                    'keyword' => $keywordValue,
                    'site_url' => $site->getUrl(),
                    'position' => null,
                    'search_page' => null,
                    'search_engine' => 'Google',
                    'device' => 'Desktop',
                    'checked_at' =>
                        (new \DateTimeImmutable())
                            ->format('Y-m-d H:i:s')
                ]
            ], Response::HTTP_OK);
        }

        // -----------------------------------------------------
        // PYTHON ERROR
        // -----------------------------------------------------

        if ($status !== 'success') {

            return new JsonResponse([
                'status' => 'error',
                'message' =>
                    $pythonResult['message']
                    ?? 'Erreur lors de la récupération du classement.',
                'data' => $pythonResult
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // -----------------------------------------------------
        // RANKING DATA
        // -----------------------------------------------------

        if (
            !array_key_exists(
                'position',
                $pythonResult
            ) ||
            !array_key_exists(
                'search_page',
                $pythonResult
            )
        ) {
            return new JsonResponse([
                'status' => 'error',
                'message' =>
                    'Python response is missing ranking data.',
                'data' => $pythonResult
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // -----------------------------------------------------
        // KEYWORD
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
                ->setCreatedAt(
                    new \DateTimeImmutable()
                );

            $this->em->persist($keyword);
        }

        // -----------------------------------------------------
        // RANKING
        // -----------------------------------------------------

        $ranking = new KeywordRanking();

        $ranking
            ->setKeyword($keyword)
            ->setAudit($audit)
            ->setSearchEngine(
                $pythonResult['search_engine']
                ?? 'google'
            )
            ->setDevice(
                $pythonResult['device']
                ?? 'desktop'
            )
            ->setPosition(
                $pythonResult['position'] !== null
                    ? (int) $pythonResult['position']
                    : null
            )
            ->setSearchPage(
                $pythonResult['search_page'] !== null
                    ? (int) $pythonResult['search_page']
                    : null
            )
            ->setCheckedAt(
                new \DateTimeImmutable()
            );

        $this->em->persist($ranking);

        // -----------------------------------------------------
        // SAVE DATABASE
        // -----------------------------------------------------

        $this->em->flush();

        // -----------------------------------------------------
        // RESPONSE
        // -----------------------------------------------------

        return new JsonResponse([
            'status' => 'success',
            'message' =>
                'Keyword ranking saved successfully.',
            'data' => [
                'id' =>
                    $ranking->getId(),

                'keyword' =>
                    $keyword->getKeyword(),

                'site' =>
                    $site->getUrl(),

                'site_url' =>
                    $site->getUrl(),

                'position' =>
                    $ranking->getPosition(),

                'search_page' =>
                    $ranking->getSearchPage(),

                'search_engine' =>
                    $ranking->getSearchEngine(),

                'device' =>
                    $ranking->getDevice(),

                'checked_at' =>
                    $ranking
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
                'message' =>
                    'Utilisateur non authentifié.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // -----------------------------------------------------
        // GET USER RANKINGS
        // -----------------------------------------------------

        $rankings = $this->keywordRankingRepository
            ->createQueryBuilder('kr')
            ->innerJoin('kr.keyword', 'k')
            ->innerJoin('k.site', 's')
            ->innerJoin('s.account', 'a')
            ->where('a.id = :userId')
            ->setParameter(
                'userId',
                $user->getId()
            )
            ->orderBy(
                'kr.checkedAt',
                'DESC'
            )
            ->getQuery()
            ->getResult();

        // -----------------------------------------------------
        // FORMAT
        // -----------------------------------------------------

        $history = [];

        foreach ($rankings as $ranking) {

            $keyword =
                $ranking->getKeyword();

            $site =
                $keyword?->getSite();

            $history[] = [
                'id' =>
                    $ranking->getId(),

                'keyword' =>
                    $keyword?->getKeyword(),

                'site' =>
                    $site?->getUrl(),

                'site_url' =>
                    $site?->getUrl(),

                'position' =>
                    $ranking->getPosition(),

                'search_page' =>
                    $ranking->getSearchPage(),

                'search_engine' =>
                    $ranking->getSearchEngine()
                    ?? 'google',

                'device' =>
                    $ranking->getDevice()
                    ?? 'desktop',

                'checked_at' =>
                    $ranking
                        ->getCheckedAt()
                        ?->format(
                            'Y-m-d H:i:s'
                        ),
            ];
        }

        // -----------------------------------------------------
        // RESPONSE
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
                'message' =>
                    'Utilisateur non authentifié.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // -----------------------------------------------------
        // FIND RANKING
        // -----------------------------------------------------

        $ranking =
            $this->keywordRankingRepository
                ->find($id);

        if (!$ranking) {
            return new JsonResponse([
                'status' => 'error',
                'message' =>
                    'Keyword ranking not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        // -----------------------------------------------------
        // KEYWORD + SITE
        // -----------------------------------------------------

        $keyword =
            $ranking->getKeyword();

        $site =
            $keyword?->getSite();

        if (!$site) {
            return new JsonResponse([
                'status' => 'error',
                'message' =>
                    'Site associated with ranking not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        // -----------------------------------------------------
        // SECURITY
        // -----------------------------------------------------

        $account =
            $site->getAccount();

        if (
            !$account ||
            $account->getId() !== $user->getId()
        ) {
            return new JsonResponse([
                'status' => 'error',
                'message' =>
                    'Vous n’avez pas le droit de supprimer ce classement.'
            ], Response::HTTP_FORBIDDEN);
        }

        // -----------------------------------------------------
        // DELETE
        // -----------------------------------------------------

        $this->em->remove($ranking);
        $this->em->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' =>
                'Keyword ranking deleted successfully.'
        ], Response::HTTP_OK);
    }
}