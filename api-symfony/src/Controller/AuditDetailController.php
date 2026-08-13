<?php

namespace App\Controller;

use App\Entity\Audit;
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

        $recommendations = array_map(
            fn($rec) => $rec->getRecommendation(),
            $audit->getRecommendations()->toArray()
        );

        return $this->json([
            'audit_id' => $audit->getId(),
            'status' => $audit->getStatus(),
            'url' => $audit->getSite()?->getUrl(),

            'global_score' => $audit->getGlobalScore(),
            'score_color' => $audit->getScoreColor(),
            'page_load_time_ms' => $audit->getPageLoadTimeMs(),

            'pagespeed_desktop_score' => $audit->getPagespeedDesktopScore(),
            'pagespeed_mobile_score' => $audit->getPagespeedMobileScore(),
            'accessibility_score' => $audit->getAccessibilityScore(),
            'best_practices_score' => $audit->getBestPracticesScore(),
            'seo_score' => $audit->getSeoScore(),

            'metrics' => $audit->getMetrics(),

            'https' => $audit->isHttps(),
            'robots_txt' => $audit->hasRobotsTxt(),
            'sitemap_xml' => $audit->hasSitemapXml(),
            'mobile_friendly' => $audit->isMobileFriendly(),

            'error_message' => $audit->getErrorMessage(),
            'created_at' => $audit->getCreatedAt()?->format(DATE_ATOM),

            'recommendations' => $recommendations,
        ]);
    }
}