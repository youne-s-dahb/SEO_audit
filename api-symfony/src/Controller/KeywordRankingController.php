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

        $data = json_decode(
            $request->getContent(),
            true
        );

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

        // -----------------------------------------------------
        // Site
        // -----------------------------------------------------

        $site = $this->siteRepository->findOneBy([
            'url' => $siteUrl
        ]);

        if (!$site) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Site not found for this URL.'
            ], Response::HTTP_NOT_FOUND);
        }

        // -----------------------------------------------------
        // SECURITY
        // Vérifier que le site appartient au user connecté
        // -----------------------------------------------------

        if ($site->getAccount() !== $user) {
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
        // Site non trouvé
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
            ->setPosition($pythonResult['position'])
            ->setSearchPage($pythonResult['search_page'])
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
        //
        // KeywordRanking
        //      ↓
        // Keyword
        //      ↓
        // Site
        //      ↓
        // account
        //      ↓
        // User
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
        // Get Site
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
        // Ranking must belong to current user
        // -----------------------------------------------------

        if ($site->getAccount() !== $user) {

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