<?php

namespace App\Controller;

use App\Entity\Audit;
use App\Entity\AuditGoogleMap;
use App\Entity\Recommendation;
use App\Entity\Site;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AuditCallbackController extends AbstractController
{
    #[Route('/api/audits/run', name: 'run_audit', methods: ['POST'])]
    public function __invoke(
        Request $request,
        EntityManagerInterface $em,
        #[CurrentUser] ?User $user
    ): JsonResponse {

        // =====================================================
        // 1. AUTH
        // =====================================================

        if (!$user) {
            return $this->json([
                'error' => 'Machi connecté.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // =====================================================
        // 2. JSON
        // =====================================================

        $body = json_decode(
            $request->getContent(),
            true
        );

        if (!is_array($body)) {
            return $this->json([
                'error' => 'Invalid JSON.'
            ], Response::HTTP_BAD_REQUEST);
        }

        // =====================================================
        // 3. URL
        // =====================================================

        $url = trim($body['url'] ?? '');

        if ($url === '') {
            return $this->json([
                'error' => 'URL is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        // =====================================================
        // 4. FIND / CREATE SITE
        // =====================================================

        $site = $em
            ->getRepository(Site::class)
            ->findOneBy([
                'url' => $url,
                'account' => $user
            ]);

        if (!$site) {

            $site = new Site();

            $site->setUrl($url);

            $site->setNormalizedUrl(
                strtolower(
                    rtrim($url, '/')
                )
            );

            $site->setName(
                parse_url(
                    $url,
                    PHP_URL_HOST
                ) ?: $url
            );

            $site->setCountryCode(
                $body['country_code'] ?? 'MA'
            );

            $site->setLanguageCode(
                $body['language_code'] ?? 'fr'
            );

            $site->setAccount($user);

            $em->persist($site);
        }

        // =====================================================
        // 5. CREATE AUDIT
        // =====================================================

        $audit = new Audit();

        $audit->setSite($site);
        $audit->setRequestedBy($user);
        $audit->setStatus('processing');
        $audit->setCreatedAt(
            new \DateTimeImmutable()
        );

        $em->persist($audit);

        /*
         * IMPORTANT:
         * Pas besoin de flush ici.
         * On sauvegardera tout à la fin.
         */

        // =====================================================
        // 6. HTTP CLIENT
        // =====================================================

        $client = HttpClient::create([
            'timeout' => 120,
            'max_duration' => 120,
        ]);

        // =====================================================
        // 7. PYTHON PAGE SPEED
        // =====================================================

        try {

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

        } catch (\Throwable $e) {

            $audit->setStatus('failed');

            $audit->setErrorMessage(
                'Audit failed: ' . $e->getMessage()
            );

            $em->flush();

            return $this->json([
                'error' => 'Impossible de terminer l\'audit.',
                'audit_id' => $audit->getId(),
            ], Response::HTTP_GATEWAY_TIMEOUT);
        }

        // =====================================================
        // 8. SAVE AUDIT DATA
        // =====================================================

        $audit->setStatus(
            $data['status'] ?? 'completed'
        );

        $audit->setGlobalScore(
            $data['global_score'] ?? null
        );

        $audit->setScoreColor(
            $data['score_color'] ?? null
        );

        // -----------------------------------------------------
        // TECHNICAL SEO
        // -----------------------------------------------------

        $audit->setIsHttps(
            $data['is_https'] ?? false
        );

        $audit->setHasRobotsTxt(
            $data['has_robots_txt'] ?? false
        );

        $audit->setHasSitemapXml(
            $data['has_sitemap_xml'] ?? false
        );

        $audit->setIsMobileFriendly(
            $data['is_mobile_friendly'] ?? false
        );

        // -----------------------------------------------------
        // PERFORMANCE
        // -----------------------------------------------------

        $audit->setPageLoadTimeMs(
            $data['page_load_time_ms'] ?? null
        );

        $audit->setPagespeedDesktopScore(
            $data['pagespeed_desktop_score'] ?? null
        );

        $audit->setPagespeedMobileScore(
            $data['pagespeed_mobile_score'] ?? null
        );

        // -----------------------------------------------------
        // LIGHTHOUSE
        // -----------------------------------------------------

        $audit->setAccessibilityScore(
            $data['accessibility_score'] ?? null
        );

        $audit->setBestPracticesScore(
            $data['best_practices_score'] ?? null
        );

        $audit->setSeoScore(
            $data['seo_score'] ?? null
        );

        // -----------------------------------------------------
        // METRICS
        // -----------------------------------------------------

        $audit->setMetrics(
            $data['metrics'] ?? []
        );

        $audit->setErrorMessage(
            $data['error_message'] ?? null
        );

        // =====================================================
        // 9. RECOMMENDATIONS
        // =====================================================

        foreach (
            ($data['recommendations'] ?? []) as $item
        ) {

            if (!is_scalar($item)) {
                continue;
            }

            $recommendation = new Recommendation();

            $recommendation->setAudit($audit);

            $recommendation->setRecommendation(
                (string) $item
            );

            $recommendation->setCreatedAt(
                new \DateTimeImmutable()
            );

            $em->persist($recommendation);
        }

        // =====================================================
        // 10. GOOGLE MAPS
        // =====================================================

        $googleMapPayload = [
            'is_present' => false,
            'status' => 'not_analyzed'
        ];

        try {

            $mapsResponse = $client->request(
                'GET',
                'http://analyzer:8000/maps/presence',
                [
                    'query' => [
                        'url' => $url
                    ]
                ]
            );

            $googleMapPayload =
                $mapsResponse->toArray();

        } catch (\Throwable $e) {

            // Google Maps ne bloque pas l'audit principal.

            $googleMapPayload = [
                'is_present' => false,
                'status' => 'not_analyzed'
            ];
        }

        // =====================================================
        // 11. SAVE GOOGLE MAPS
        // =====================================================

        $googleMap = new AuditGoogleMap();

        $googleMap->setAudit($audit);

        $isPresent =
            (bool) (
                $googleMapPayload['is_present']
                ?? $googleMapPayload['isPresent']
                ?? false
            );

        $businessName =
            $googleMapPayload['business_name']
            ?? $googleMapPayload['businessName']
            ?? null;

        $title =
            $googleMapPayload['title']
            ?? null;

        $address =
            $googleMapPayload['address']
            ?? null;

        $rating =
            $googleMapPayload['rating']
            ?? null;

        $reviewsCount =
            $googleMapPayload['reviews_count']
            ?? $googleMapPayload['reviewsCount']
            ?? null;

        $placeId =
            $googleMapPayload['place_id']
            ?? $googleMapPayload['placeId']
            ?? null;

        $googleMap->setIsPresent(
            $isPresent
        );

        $googleMap->setBusinessName(
            is_scalar($businessName)
                ? (string) $businessName
                : null
        );

        $googleMap->setTitle(
            is_scalar($title)
                ? (string) $title
                : null
        );

        $googleMap->setAddress(
            is_scalar($address)
                ? (string) $address
                : null
        );

        $googleMap->setRating(
            is_numeric($rating)
                ? (float) $rating
                : null
        );

        $googleMap->setReviewsCount(
            is_numeric($reviewsCount)
                ? (int) $reviewsCount
                : null
        );

        $googleMap->setPlaceId(
            is_scalar($placeId)
                ? (string) $placeId
                : null
        );

        $em->persist($googleMap);

        // =====================================================
        // 12. ONE FINAL FLUSH
        // =====================================================

        $em->flush();

        // =====================================================
        // 13. RESPONSE
        // =====================================================

        return $this->json([

            'message' =>
                'Audit completed successfully',

            'audit_id' =>
                $audit->getId(),

            'status' =>
                $audit->getStatus(),

            'url' =>
                $site->getUrl(),

            // GLOBAL
            'global_score' =>
                $audit->getGlobalScore(),

            'score_color' =>
                $audit->getScoreColor(),

            // PERFORMANCE
            'page_load_time_ms' =>
                $audit->getPageLoadTimeMs(),

            'pagespeed_desktop_score' =>
                $audit->getPagespeedDesktopScore(),

            'pagespeed_mobile_score' =>
                $audit->getPagespeedMobileScore(),

            // LIGHTHOUSE
            'accessibility_score' =>
                $audit->getAccessibilityScore(),

            'best_practices_score' =>
                $audit->getBestPracticesScore(),

            'seo_score' =>
                $audit->getSeoScore(),

            // TECHNICAL SEO
            'https' =>
                $audit->isHttps(),

            'robots_txt' =>
                $audit->hasRobotsTxt(),

            'sitemap_xml' =>
                $audit->hasSitemapXml(),

            'mobile_friendly' =>
                $audit->isMobileFriendly(),

            // METRICS
            'metrics' =>
                $audit->getMetrics(),

            // GOOGLE MAPS
            'google_maps' => [

                'is_present' =>
                    $googleMap->isPresent(),

                'business_name' =>
                    $googleMap->getBusinessName(),

                'title' =>
                    $googleMap->getTitle(),

                'address' =>
                    $googleMap->getAddress(),

                'rating' =>
                    $googleMap->getRating(),

                'reviews_count' =>
                    $googleMap->getReviewsCount(),

                'place_id' =>
                    $googleMap->getPlaceId(),

                'status' =>
                    $googleMapPayload['status']
                    ?? (
                        $googleMap->isPresent()
                            ? 'present'
                            : 'not_found'
                    ),
            ],

            'created_at' =>
                $audit->getCreatedAt()
                    ?->format(
                        \DateTimeInterface::ATOM
                    ),
        ]);
    }
}