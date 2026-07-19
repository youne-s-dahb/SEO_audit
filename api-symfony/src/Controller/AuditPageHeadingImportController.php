<?php

namespace App\Controller;

use App\Entity\Audit;
use App\Entity\AuditPage;
use App\Entity\AuditPageHeading;
use Doctrine\ORM\EntityManagerInterface;
use Predis\Client as RedisClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AuditPageHeadingImportController extends AbstractController
{
    #[Route('/api/audit-page-heading/import', name: 'audit_page_heading_import', methods: ['POST'])]
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

        $auditPage = $em->getRepository(AuditPage::class)->findOneBy([
            'audit' => $audit,
            'url' => $url,
        ]);

        if (!$auditPage) {
            return $this->json([
                'error' => 'AuditPage not found for this url/audit. Call /api/audit-page/import first.',
            ], 404);
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

        $headingsData = $data['headings'] ?? [];

        // -----------------------------
        // OPTIMIZATION 1: Delete en masse (Bulk Delete) f requête wahed unique!
        // -----------------------------
        $em->getRepository(AuditPageHeading::class)->createQueryBuilder('h')
            ->delete()
            ->where('h.auditPage = :auditPage')
            ->setParameter('auditPage', $auditPage)
            ->getQuery()
            ->execute();

        // -----------------------------
        // Creer les nouveaux headings
        // -----------------------------
        $insertedCount = 0;

        foreach ($headingsData as $item) {
            if (empty($item['heading_level']) || !isset($item['content'])) {
                continue;
            }

            $heading = new AuditPageHeading();
            $heading->setAuditPage($auditPage);
            
            // Casting safe bach n-hemaw l-base de données
            $heading->setHeadingLevel((string) $item['heading_level']);
            $heading->setContent((string) $item['content']);
            $heading->setPosition(isset($item['position']) ? (int) $item['position'] : null);

            $em->persist($heading);
            $insertedCount++;
        }

        try {
            $em->flush();
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Database error while saving headings.',
                'message' => $e->getMessage(),
            ], 500);
        }

        return $this->json([
            'message' => 'AuditPageHeading stored successfully',
            'audit_page_id' => $auditPage->getId(),
            'headings_count' => $insertedCount,
        ]);
    }
}