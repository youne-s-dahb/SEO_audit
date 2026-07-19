<?php

namespace App\Controller;

use App\Entity\Audit;
use App\Entity\AuditPage;
use App\Entity\AuditKeywordDensity;
use Doctrine\ORM\EntityManagerInterface;
use Predis\Client as RedisClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AuditKeywordDensityImportController extends AbstractController
{
    #[Route('/api/audit-keyword-density/import', name: 'audit_keyword_density_import', methods: ['POST'])]
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

        // -----------------------------
        // Recuperer l'Audit parent
        // -----------------------------
        $audit = $em->getRepository(Audit::class)->find($auditId);
        if (!$audit) {
            return $this->json(['error' => "Audit with id {$auditId} not found"], 404);
        }

        // -----------------------------
        // Recuperer l'AuditPage parent
        // -----------------------------
        $auditPage = $em->getRepository(AuditPage::class)->findOneBy([
            'audit' => $audit,
            'url' => $url,
        ]);

        if (!$auditPage) {
            return $this->json([
                'error' => 'AuditPage not found for this url/audit. '
                    . 'Call /api/audit-page/import first.',
            ], 404);
        }

        // -----------------------------
        // Lire les donnees on-page dans Redis
        // -----------------------------
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

        $keywordsData = $data['keyword_density'] ?? [];

        // -----------------------------
        // Idempotence: bulk delete des anciens mots-cles de cette page
        // -----------------------------
        $em->getRepository(AuditKeywordDensity::class)->createQueryBuilder('k')
            ->delete()
            ->where('k.auditPage = :auditPage')
            ->setParameter('auditPage', $auditPage)
            ->getQuery()
            ->execute();

        // -----------------------------
        // Creer les nouveaux mots-cles (liste courte, max top_n ~10-20,
        // pas besoin de batch processing / clear() ici)
        // -----------------------------
        $insertedCount = 0;

        foreach ($keywordsData as $item) {
            if (empty($item['keyword'])) {
                continue;
            }

            $keyword = new AuditKeywordDensity();
            $keyword->setAuditPage($auditPage);

            // Trim + troncature 255 caracteres (securite supplementaire,
            // deja fait cote Python, mais double securite pour la colonne)
            $cleanKeyword = trim((string) $item['keyword']);
            $keyword->setKeyword(mb_substr($cleanKeyword, 0, 255));

            $keyword->setOccurrences(isset($item['occurrences']) ? (int) $item['occurrences'] : null);
            $keyword->setDensityPercent(isset($item['density_percent']) ? (float) $item['density_percent'] : null);

            $em->persist($keyword);
            $insertedCount++;
        }

        try {
            $em->flush();
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Database error while saving keyword density.',
                'message' => $e->getMessage(),
            ], 500);
        }

        return $this->json([
            'message' => 'AuditKeywordDensity stored successfully',
            'audit_page_id' => $auditPage->getId(),
            'keywords_count' => $insertedCount,
        ]);
    }
}