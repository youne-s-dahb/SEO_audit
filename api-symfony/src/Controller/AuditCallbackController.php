<?php

namespace App\Controller;

use App\Entity\Audit;
use App\Entity\Site;
use App\Entity\User;
use App\Entity\Recommendation;
use Doctrine\ORM\EntityManagerInterface;
use Predis\Client as RedisClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AuditCallbackController extends AbstractController
{
    #[Route('/api/audit/callback', name: 'audit_callback', methods: ['POST'])]
    public function __invoke(
        Request $request,
        EntityManagerInterface $em,
        RedisClient $redis
    ): JsonResponse {

        $payload = json_decode($request->getContent(), true);

        if (empty($payload['url'])) {
            return $this->json([
                'error' => 'URL is required'
            ], 400);
        }

        $url = $payload['url'];

        $redisKey = 'audit:' . $url;

        $cached = $redis->get($redisKey);

        if (!$cached) {
            return $this->json([
                'error' => 'Audit not found in Redis'
            ], 404);
        }

        $auditData = json_decode($cached, true);

        $site = $em->getRepository(Site::class)->findOneBy([
            'url' => $url
        ]);

        if (!$site) {
                $site = new Site();

                $site->setUrl($url);
                $site->setNormalizedUrl(strtolower(rtrim($url, '/')));
                $site->setName(parse_url($url, PHP_URL_HOST));

                // Valeurs par défaut
                $site->setCountryCode('MA');
                $site->setLanguageCode('fr');

                // User propriétaire
                $user = $em->getRepository(User::class)->find(2);

                if (!$user) {
                    return $this->json([
                        'error' => 'User  not found'
                    ], 500);
                }

                $site->setAccount($user);
              
                $em->persist($site);
            }
        $audit = new Audit();

        $audit->setSite($site);
        $audit->setRequestedBy($site->getAccount());
        $audit->setStatus($auditData['status'] ?? 'completed');
        $audit->setGlobalScore($auditData['global_score'] ?? null);
        $audit->setScoreColor($auditData['score_color'] ?? null);
        $audit->setIsHttps($auditData['is_https'] ?? false);
        $audit->setHasRobotsTxt($auditData['has_robots_txt'] ?? false);
        $audit->setHasSitemapXml($auditData['has_sitemap_xml'] ?? false);
        $audit->setIsMobileFriendly($auditData['is_mobile_friendly'] ?? false);
        $audit->setPageLoadTimeMs($auditData['page_load_time_ms'] ?? null);
        $audit->setPagespeedDesktopScore($auditData['pagespeed_desktop_score'] ?? null);
        $audit->setPagespeedMobileScore($auditData['pagespeed_mobile_score'] ?? null);
        $audit->setAccessibilityScore($auditData['accessibility_score'] ?? null);
        $audit->setBestPracticesScore($auditData['best_practices_score'] ?? null);
        $audit->setSeoScore($auditData['seo_score'] ?? null);
        $audit->setMetrics($auditData['metrics'] ?? null);
        $audit->setErrorMessage($auditData['error_message'] ?? null);

        $audit->setCreatedAt(new \DateTimeImmutable());
        $em->persist($audit);
        $em->flush();
        foreach ($auditData['recommendations'] ?? [] as $item) {

          $recommendation = new Recommendation();

          $recommendation->setRecommendation($item);
          $recommendation->setCreatedAt(new \DateTimeImmutable());
          $recommendation->setAudit($audit);

          $em->persist($recommendation);
        }

        $em->flush();
     

        

        $redis->del($redisKey);

        return $this->json([
            'message' => 'Audit stored successfully',
            'audit_id' => $audit->getId()
        ]);
    }
}