<?php

namespace App\Controller;

use App\Entity\Audit;
use App\Entity\Recommendation;
use App\Entity\Site;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Attribute\Route;

class AuditCallbackController extends AbstractController
{
    #[Route('/api/audits/run', name: 'run_audit', methods: ['POST'])]
    public function __invoke(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {

        $body = json_decode($request->getContent(), true);

        if (empty($body['url'])) {
            return $this->json([
                'error' => 'URL is required'
            ], 400);
        }

        $url = trim($body['url']);

        // Recherche du site
        $site = $em->getRepository(Site::class)->findOneBy([
            'url' => $url
        ]);

        if (!$site) {

            $user = $em->getRepository(User::class)->find(2);

            if (!$user) {
                return $this->json([
                    'error' => 'User not found'
                ], 404);
            }

            $site = new Site();
            $site->setUrl($url);
            $site->setNormalizedUrl(strtolower(rtrim($url, '/')));
            $site->setName(parse_url($url, PHP_URL_HOST));
            $site->setCountryCode($data['country_code'] ?? 'MA');
            $site->setLanguageCode($data['language_code'] ?? 'fr');
            $site->setAccount($user);

            $em->persist($site);
        }

        $audit = new Audit();
        $audit->setSite($site);
        $audit->setRequestedBy($site->getAccount());
        $audit->setStatus('processing');
        $audit->setCreatedAt(new \DateTimeImmutable());

        $em->persist($audit);
        $em->flush();

        $client = HttpClient::create();

        $response = $client->request(
            'GET',
            'http://analyzer:8000/audit',
            [
                'query' => [
                    'url' => $url
                ]
            ]
        );

        $data = $response->toArray();

        $audit->setStatus($data['status'] ?? 'completed');
        $audit->setGlobalScore($data['global_score'] ?? null);
        $audit->setScoreColor($data['score_color'] ?? null);

        $audit->setIsHttps($data['is_https'] ?? false);
        $audit->setHasRobotsTxt($data['has_robots_txt'] ?? false);
        $audit->setHasSitemapXml($data['has_sitemap_xml'] ?? false);
        $audit->setIsMobileFriendly($data['is_mobile_friendly'] ?? false);

        $audit->setPageLoadTimeMs($data['page_load_time_ms'] ?? null);

        $audit->setPagespeedDesktopScore($data['pagespeed_desktop_score'] ?? null);
        $audit->setPagespeedMobileScore($data['pagespeed_mobile_score'] ?? null);

        $audit->setAccessibilityScore($data['accessibility_score'] ?? null);
        $audit->setBestPracticesScore($data['best_practices_score'] ?? null);
        $audit->setSeoScore($data['seo_score'] ?? null);

        $audit->setMetrics($data['metrics'] ?? []);
        $audit->setErrorMessage($data['error_message'] ?? null);

        foreach ($data['recommendations'] ?? [] as $item) {

            $recommendation = new Recommendation();

            $recommendation->setAudit($audit);
            $recommendation->setRecommendation($item);
            $recommendation->setCreatedAt(new \DateTimeImmutable());

            $em->persist($recommendation);
        }

        $em->flush();

        return $this->json([
            'message' => 'Audit completed successfully',
            'audit_id' => $audit->getId(),
            'score' => $audit->getGlobalScore()
        ], Response::HTTP_OK);
    }
}