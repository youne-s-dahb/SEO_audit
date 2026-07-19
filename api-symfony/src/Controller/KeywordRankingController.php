<?php

namespace App\Controller;

use App\Entity\Keyword;
use App\Entity\KeywordRanking;
use App\Repository\AuditRepository;
use App\Repository\KeywordRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Attribute\Route;

class KeywordRankingController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SiteRepository $siteRepository,
        private AuditRepository $auditRepository,
        private KeywordRepository $keywordRepository
    ) {
    }

    #[Route('/api/keyword-ranking', name: 'keyword_ranking', methods: ['POST'])]
    public function index(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (
            !isset($data['site_id']) ||
            !isset($data['audit_id']) ||
            empty($data['keyword'])
        ) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'site_id, audit_id and keyword are required.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $site = $this->siteRepository->find($data['site_id']);

        if (!$site) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Site not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        $audit = $this->auditRepository->find($data['audit_id']);

        if (!$audit) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Audit not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        $client = HttpClient::create();

        try {

            $response = $client->request(
                'GET',
                'http://analyzer:8000/serp/get-ranking',
                [
                    'query' => [
                        'keyword' => $data['keyword'],
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

       $status = $pythonResult['status'] ?? null;

        if ($status === 'not_found') {
            return new JsonResponse([
                'status' => 'not_found',
                'message' => 'Le site n’est pas classé pour ce mot-clé.'
            ], 200);
        }

        if ($status !== 'success') {
            return new JsonResponse($pythonResult, Response::HTTP_OK);
        }

        $keyword = $this->keywordRepository->findOneBy([
            'keyword' => $data['keyword'],
            'site' => $site
        ]);

        if (!$keyword) {

            $keyword = new Keyword();

            $keyword
                ->setKeyword($data['keyword'])
                ->setSite($site)
                ->setCreatedAt(new \DateTimeImmutable());

            $this->em->persist($keyword);
        }

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
                'checked_at' => $ranking->getCheckedAt()->format('Y-m-d H:i:s'),
            ]
        ], Response::HTTP_CREATED);
    }
}