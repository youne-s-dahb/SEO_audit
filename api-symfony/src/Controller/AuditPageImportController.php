<?php

namespace App\Controller;

use App\Entity\Audit;
use App\Entity\AuditPage;
use Doctrine\ORM\EntityManagerInterface;
use Predis\Client as RedisClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AuditPageImportController extends AbstractController
{
    #[Route('/api/audit-page/import', name: 'audit_page_import', methods: ['POST'])]
    public function __invoke(
        Request $request,
        EntityManagerInterface $em,
        RedisClient $redis
    ): JsonResponse {

        $payload = json_decode($request->getContent(), true);

        if (empty($payload['url']) || empty($payload['audit_id'])) {
            return $this->json([
                'error' => 'url and audit_id are required fields'
            ], 400);
        }

        $url = $payload['url'];
        $auditId = $payload['audit_id'];

        $audit = $em->getRepository(Audit::class)->find($auditId);
        if (!$audit) {
            return $this->json(['error' => "Audit with id {$auditId} not found"], 404);
        }

        // Lire Redis
        $redisKey = 'audit:onpage:' . $url;
        $cached = $redis->get($redisKey);

        if (!$cached) {
            return $this->json([
                'error' => 'On-page audit data not found in Redis.',
                'redis_key' => $redisKey,
            ], 404);
        }

        $data = json_decode($cached, true);
        if (($data['status'] ?? null) !== 'success') {
            return $this->json([
                'error' => 'On-page scraping failed for this URL',
                'details' => $data,
            ], 422);
        }

        $pageData = $data['page'] ?? [];

        // OPTIMIZATION 1: Eviter les doublons f l-base de données
        $auditPage = $em->getRepository(AuditPage::class)->findOneBy([
            'audit' => $audit,
            'url' => $pageData['url'] ?? $url
        ]);

        if (!$auditPage) {
            $auditPage = new AuditPage();
            $auditPage->setAudit($audit);
            $auditPage->setCreatedAt(new \DateTimeImmutable());
        }

        // Mapping + Type Casting khfif bach nhemaw l-Entity
        $auditPage->setUrl($pageData['url'] ?? $url);
        $auditPage->setStatusCode(isset($pageData['status_code']) ? (int)$pageData['status_code'] : null);
        $auditPage->setTitle($pageData['title'] ?? null);
        $auditPage->setTitleLength(isset($pageData['title_length']) ? (int)$pageData['title_length'] : null);
        $auditPage->setMetaDescription($pageData['meta_description'] ?? null);
        $auditPage->setMetaLength(isset($pageData['meta_length']) ? (int)$pageData['meta_length'] : null);
        $auditPage->setCanonicalUrl($pageData['canonical_url'] ?? null);
        $auditPage->setMetaRobots($pageData['meta_robots'] ?? null);
        $auditPage->setLangAttribute($pageData['lang_attribute'] ?? null);
        $auditPage->setH1Count(isset($pageData['h1_count']) ? (int)$pageData['h1_count'] : null);
        $auditPage->setH1IsUnique(isset($pageData['h1_is_unique']) ? (bool)$pageData['h1_is_unique'] : null);
        $auditPage->setWordCount(isset($pageData['word_count']) ? (int)$pageData['word_count'] : null);
        $auditPage->setInternalLinksCount(isset($pageData['internal_links_count']) ? (int)$pageData['internal_links_count'] : null);
        $auditPage->setExternalLinksCount(isset($pageData['external_links_count']) ? (int)$pageData['external_links_count'] : null);
        $auditPage->setBrokenLinksCount(isset($pageData['broken_links_count']) ? (int)$pageData['broken_links_count'] : null);

        // Colonnes NOT NULL
        $auditPage->setImagesCount((int)($pageData['images_count'] ?? 0));
        $auditPage->setImagesWithoutAltCount((int)($pageData['images_without_alt_count'] ?? 0));
        $auditPage->setHasStructuredData((bool)($pageData['has_structured_data'] ?? false));
        $auditPage->setCrawlDepth((int)($pageData['crawl_depth'] ?? 0));

        $auditPage->setViewportMeta($pageData['viewport_meta'] ?? null);
        $auditPage->setIsHttps(isset($pageData['is_https']) ? (bool)$pageData['is_https'] : null);
        $auditPage->setResponseTimeMs(isset($pageData['response_time_ms']) ? (int)$pageData['response_time_ms'] : null);
        $auditPage->setLoadTimeMs(isset($pageData['load_time_ms']) ? (int)$pageData['load_time_ms'] : null);

        // OPTIMIZATION 3: Try Catch 3la wed DB issues
        try {
            $em->persist($auditPage);
            $em->flush();
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Database error while saving the audit page.',
                'message' => $e->getMessage() // f production hiyed had l-message t-eviter l leaks dyal l infstrasructure
            ], 500);
        }

        return $this->json([
            'message' => 'AuditPage stored successfully',
            'audit_page_id' => $auditPage->getId(),
        ]);
    }
}