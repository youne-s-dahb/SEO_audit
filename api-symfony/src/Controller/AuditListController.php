<?php

namespace App\Controller;

use App\Entity\Audit;
use App\Entity\AuditGoogleMap;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AuditListController extends AbstractController
{
    #[Route(
        '/api/audits',
        name: 'audit_list',
        methods: ['GET'],
        priority: 100
    )]
    public function __invoke(
        EntityManagerInterface $em,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(
                ['message' => 'Machi connecté.'],
                401
            );
        }

        $audits = $em
            ->getRepository(Audit::class)
            ->findBy(
                ['requestedBy' => $user],
                ['createdAt' => 'DESC']
            );

        $data = array_map(
            function (Audit $audit) {
                $site = $audit->getSite();
                $siteUrl = $site?->getUrl();
                $metrics = $audit->getMetrics() ?? [];

                $normalizedMetrics = [
                    'firstContentfulPaint' => $metrics['firstContentfulPaint'] ?? $metrics['first_contentful_paint'] ?? null,
                    'first_contentful_paint' => $metrics['firstContentfulPaint'] ?? $metrics['first_contentful_paint'] ?? null,
                    'largestContentfulPaint' => $metrics['largestContentfulPaint'] ?? $metrics['largest_contentful_paint'] ?? null,
                    'largest_contentful_paint' => $metrics['largestContentfulPaint'] ?? $metrics['largest_contentful_paint'] ?? null,
                    'speedIndex' => $metrics['speedIndex'] ?? $metrics['speed_index'] ?? null,
                    'speed_index' => $metrics['speedIndex'] ?? $metrics['speed_index'] ?? null,
                    'totalBlockingTime' => $metrics['totalBlockingTime'] ?? $metrics['total_blocking_time'] ?? null,
                    'total_blocking_time' => $metrics['totalBlockingTime'] ?? $metrics['total_blocking_time'] ?? null,
                    'cumulativeLayoutShift' => $metrics['cumulativeLayoutShift'] ?? $metrics['cumulative_layout_shift'] ?? null,
                    'cumulative_layout_shift' => $metrics['cumulativeLayoutShift'] ?? $metrics['cumulative_layout_shift'] ?? null,
                    'timeToInteractive' => $metrics['timeToInteractive'] ?? $metrics['time_to_interactive'] ?? null,
                    'time_to_interactive' => $metrics['timeToInteractive'] ?? $metrics['time_to_interactive'] ?? null,
                    'loadingTime' => $metrics['loadingTime'] ?? $metrics['loading_time'] ?? null,
                    'loading_time' => $metrics['loadingTime'] ?? $metrics['loading_time'] ?? null,
                ];

                foreach ($normalizedMetrics as $key => $value) {
                    if ($value !== null && is_numeric($value)) {
                        $normalizedMetrics[$key] = (float) $value;
                    }
                }

                $score = $audit->getGlobalScore();
                $googleMap = $audit->getGoogleMap();
                $siteName = $this->normalizeSiteName($site?->getName(), $siteUrl);
                $googleMapData = $this->normalizeGoogleMap($googleMap);

                return [
                    'id' => $audit->getId(),
                    'siteName' => $siteName,
                    'site_name' => $siteName,
                    'site' => [
                        'name' => $siteName,
                        'url' => $siteUrl,
                    ],
                    'url' => $siteUrl,
                    'status' => $audit->getStatus(),
                    'score' => $score,
                    'globalScore' => $score,
                    'global_score' => $score,
                    'desktopScore' => $audit->getPagespeedDesktopScore(),
                    'mobileScore' => $audit->getPagespeedMobileScore(),
                    'scoreColor' => $audit->getScoreColor(),
                    'score_color' => $audit->getScoreColor(),
                    'createdAt' => $audit->getCreatedAt()?->format(DATE_ATOM),
                    'created_at' => $audit->getCreatedAt()?->format(DATE_ATOM),
                    'pageLoadTimeMs' => $audit->getPageLoadTimeMs(),
                    'page_load_time_ms' => $audit->getPageLoadTimeMs(),
                    'pagespeedDesktopScore' => $audit->getPagespeedDesktopScore(),
                    'pagespeed_desktop_score' => $audit->getPagespeedDesktopScore(),
                    'pagespeedMobileScore' => $audit->getPagespeedMobileScore(),
                    'pagespeed_mobile_score' => $audit->getPagespeedMobileScore(),
                    'accessibilityScore' => $audit->getAccessibilityScore(),
                    'accessibility_score' => $audit->getAccessibilityScore(),
                    'bestPracticesScore' => $audit->getBestPracticesScore(),
                    'best_practices_score' => $audit->getBestPracticesScore(),
                    'seoScore' => $audit->getSeoScore(),
                    'seo_score' => $audit->getSeoScore(),
                    'https' => $audit->isHttps(),
                    'robotsTxt' => $audit->hasRobotsTxt(),
                    'robots_txt' => $audit->hasRobotsTxt(),
                    'sitemapXml' => $audit->hasSitemapXml(),
                    'sitemap_xml' => $audit->hasSitemapXml(),
                    'mobileFriendly' => $audit->isMobileFriendly(),
                    'mobile_friendly' => $audit->isMobileFriendly(),
                    'technicalSeo' => [
                        'https' => $audit->isHttps(),
                        'robotsTxt' => $audit->hasRobotsTxt(),
                        'robots_txt' => $audit->hasRobotsTxt(),
                        'sitemapXml' => $audit->hasSitemapXml(),
                        'sitemap_xml' => $audit->hasSitemapXml(),
                        'mobileFriendly' => $audit->isMobileFriendly(),
                        'mobile_friendly' => $audit->isMobileFriendly(),
                        'pageLoadTimeMs' => $audit->getPageLoadTimeMs(),
                        'page_load_time_ms' => $audit->getPageLoadTimeMs(),
                    ],
                    'metrics' => array_merge($normalizedMetrics, $metrics),
                    'errorMessage' => $audit->getErrorMessage(),
                    'error_message' => $audit->getErrorMessage(),
                    'googleMap' => $googleMapData,
                    'google_map' => $googleMapData,
                    'googleMaps' => $googleMapData,
                    'google_maps' => $googleMapData,
                    'googleMapsUrl' => $googleMapData['googleMapsUrl'] ?? null,
                    'google_maps_url' => $googleMapData['googleMapsUrl'] ?? null,
                    'auditType' => $googleMapData ? 'google_maps' : 'seo',
                    'audit_type' => $googleMapData ? 'google_maps' : 'seo',
                ];
            },
            $audits
        );

        return $this->json($data);
    }

    private function normalizeSiteName(?string $siteName, ?string $siteUrl): string
    {
        $candidate = trim((string) $siteName);

        if ($candidate !== '' && !in_array(strtolower($candidate), ['site', 'untitled site'], true)) {
            return $candidate;
        }

        if (is_string($siteUrl) && $siteUrl !== '') {
            $host = parse_url($siteUrl, PHP_URL_HOST) ?: '';

            if ($host === '') {
                $host = preg_replace('#^https?://#i', '', $siteUrl) ?: '';
                $host = explode('/', $host)[0] ?? '';
            }

            $host = preg_replace('/^www\./i', '', strtolower((string) $host));

            if ($host !== '') {
                return $host;
            }

            return trim((string) $siteUrl);
        }

        return 'Untitled site';
    }

    private function normalizeGoogleMap(?AuditGoogleMap $googleMap): ?array
    {
        if (!$googleMap) {
            return null;
        }

        $placeId = $googleMap->getPlaceId();
        $googleMapsUrl = is_string($placeId) && $placeId !== ''
            ? sprintf('https://www.google.com/maps/place/?q=place_id:%s', rawurlencode($placeId))
            : null;

        return [
            'id' => $googleMap->getId(),
            'status' => $googleMap->isPresent() ? 'present' : 'not_found',
            'isPresent' => $googleMap->isPresent(),
            'is_present' => $googleMap->isPresent(),
            'businessName' => $googleMap->getBusinessName(),
            'business_name' => $googleMap->getBusinessName(),
            'title' => $googleMap->getTitle(),
            'address' => $googleMap->getAddress(),
            'rating' => $googleMap->getRating(),
            'reviewsCount' => $googleMap->getReviewsCount(),
            'reviews_count' => $googleMap->getReviewsCount(),
            'placeId' => $placeId,
            'place_id' => $placeId,
            'googleMapsUrl' => $googleMapsUrl,
            'google_maps_url' => $googleMapsUrl,
        ];
    }
}