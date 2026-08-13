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

class AuditDetailController extends AbstractController
{
    #[Route('/api/audits/{id}/report', name: 'audit_detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function __invoke(
        int $id,
        EntityManagerInterface $em,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['message' => 'Machi connecté.'], 401);
        }

        $audit = $em->getRepository(Audit::class)->find($id);

        if (!$audit) {
            return $this->json(['message' => 'Audit machi mawjoud.'], 404);
        }

        if ($audit->getRequestedBy()?->getId() !== $user->getId()) {
            return $this->json(['message' => "Ma3ndekch l'access l had audit."], 403);
        }

        $site = $audit->getSite();
        $siteUrl = $site?->getUrl();

        $googleMap = $audit->getGoogleMap();
        $googleMapData = $this->normalizeGoogleMap($googleMap);
        $auditType = $googleMapData ? 'google_maps' : 'seo';
        $siteName = $this->normalizeSiteName($site?->getName(), $siteUrl);
        $recommendations = array_values(array_filter(array_map(
            static fn($rec) => trim((string) $rec->getRecommendation()),
            $audit->getRecommendations()->toArray()
        ), static fn(string $recommendation) => $recommendation !== ''));

        $metrics = $this->normalizeMetrics($audit->getMetrics());
        $technicalSeo = [
            'https' => $audit->isHttps(),
            'robotsTxt' => $audit->hasRobotsTxt(),
            'robots_txt' => $audit->hasRobotsTxt(),
            'sitemapXml' => $audit->hasSitemapXml(),
            'sitemap_xml' => $audit->hasSitemapXml(),
            'mobileFriendly' => $audit->isMobileFriendly(),
            'mobile_friendly' => $audit->isMobileFriendly(),
            'pageLoadTimeMs' => $audit->getPageLoadTimeMs(),
            'page_load_time_ms' => $audit->getPageLoadTimeMs(),
        ];

        $score = $audit->getGlobalScore();
        $desktopScore = $audit->getPagespeedDesktopScore();
        $mobileScore = $audit->getPagespeedMobileScore();
        $seoScore = $audit->getSeoScore();
        $accessibilityScore = $audit->getAccessibilityScore();
        $bestPracticesScore = $audit->getBestPracticesScore();

        return $this->json([
            'id' => $audit->getId(),
            'audit_id' => $audit->getId(),
            'status' => $audit->getStatus(),
            'siteName' => $siteName,
            'site_name' => $siteName,
            'site' => [
                'name' => $siteName,
                'url' => $siteUrl,
            ],
            'url' => $siteUrl,
            'auditType' => $auditType,
            'audit_type' => $auditType,
            'score' => $score,
            'globalScore' => $score,
            'global_score' => $score,
            'scoreColor' => $audit->getScoreColor(),
            'score_color' => $audit->getScoreColor(),
            'scores' => [
                'global' => $score,
                'desktop' => $desktopScore,
                'mobile' => $mobileScore,
                'seo' => $seoScore,
                'accessibility' => $accessibilityScore,
                'bestPractices' => $bestPracticesScore,
            ],
            'pageLoadTimeMs' => $audit->getPageLoadTimeMs(),
            'page_load_time_ms' => $audit->getPageLoadTimeMs(),
            'desktopScore' => $desktopScore,
            'pagespeedDesktopScore' => $desktopScore,
            'pagespeed_desktop_score' => $desktopScore,
            'mobileScore' => $mobileScore,
            'pagespeedMobileScore' => $mobileScore,
            'pagespeed_mobile_score' => $mobileScore,
            'accessibilityScore' => $accessibilityScore,
            'accessibility_score' => $accessibilityScore,
            'bestPracticesScore' => $bestPracticesScore,
            'best_practices_score' => $bestPracticesScore,
            'seoScore' => $seoScore,
            'seo_score' => $seoScore,
            'metrics' => $metrics,
            'technicalSeo' => $technicalSeo,
            'technical_seo' => $technicalSeo,
            'https' => $audit->isHttps(),
            'robotsTxt' => $audit->hasRobotsTxt(),
            'robots_txt' => $audit->hasRobotsTxt(),
            'sitemapXml' => $audit->hasSitemapXml(),
            'sitemap_xml' => $audit->hasSitemapXml(),
            'mobileFriendly' => $audit->isMobileFriendly(),
            'mobile_friendly' => $audit->isMobileFriendly(),
            'errorMessage' => $audit->getErrorMessage(),
            'error_message' => $audit->getErrorMessage(),
            'createdAt' => $audit->getCreatedAt()?->format(DATE_ATOM),
            'created_at' => $audit->getCreatedAt()?->format(DATE_ATOM),
            'googleMapsUrl' => $googleMapData['googleMapsUrl'] ?? null,
            'google_maps_url' => $googleMapData['googleMapsUrl'] ?? null,
            'googleMap' => $googleMapData,
            'google_map' => $googleMapData,
            'googleMaps' => $googleMapData,
            'google_maps' => $googleMapData,
            'recommendations' => $recommendations,
        ]);
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

    private function normalizeMetrics(?array $metrics): array
    {
        $metrics = $metrics ?? [];

        $map = [
            'firstContentfulPaint' => 'first_contentful_paint',
            'largestContentfulPaint' => 'largest_contentful_paint',
            'speedIndex' => 'speed_index',
            'totalBlockingTime' => 'total_blocking_time',
            'cumulativeLayoutShift' => 'cumulative_layout_shift',
            'timeToInteractive' => 'time_to_interactive',
        ];

        $normalized = [];

        foreach ($map as $camelKey => $snakeKey) {
            $value = $metrics[$camelKey] ?? $metrics[$snakeKey] ?? null;
            $normalized[$camelKey] = $value;
            $normalized[$snakeKey] = $value;
        }

        return array_merge($normalized, $metrics);
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