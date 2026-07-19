<?php

namespace App\Controller;

use App\Entity\Audit;
use App\Entity\AuditPage;
use App\Entity\AuditPageImage;
use Doctrine\ORM\EntityManagerInterface;
use Predis\Client as RedisClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AuditPageImageImportController extends AbstractController
{
    #[Route('/api/audit-page-image/import', name: 'audit_page_image_import', methods: ['POST'])]
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

        $imagesData = $data['images'] ?? [];

        // -----------------------------
        // Idempotence: bulk delete des anciennes images de cette page
        // -----------------------------
        $em->getRepository(AuditPageImage::class)->createQueryBuilder('i')
            ->delete()
            ->where('i.auditPage = :auditPage')
            ->setParameter('auditPage', $auditPage)
            ->getQuery()
            ->execute();

        // -----------------------------
        // Creer les nouvelles images
        // -----------------------------
        $insertedCount = 0;

        foreach ($imagesData as $item) {
            if (empty($item['image_url'])) {
                continue;
            }

            $image = new AuditPageImage();
            $image->setAuditPage($auditPage);
            $image->setImageUrl((string) $item['image_url']);
            $image->setHasAlt(isset($item['has_alt']) ? (bool) $item['has_alt'] : null);
            $image->setAltText(isset($item['alt_text']) ? (string) $item['alt_text'] : null);
            $image->setFileSizeKb(isset($item['file_size_kb']) ? (float) $item['file_size_kb'] : null);
            $image->setImageType(isset($item['image_type']) ? (string) $item['image_type'] : null);

            $em->persist($image);
            $insertedCount++;
        }

        try {
            $em->flush();
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Database error while saving images.',
                'message' => $e->getMessage(),
            ], 500);
        }

        return $this->json([
            'message' => 'AuditPageImage stored successfully',
            'audit_page_id' => $auditPage->getId(),
            'images_count' => $insertedCount,
        ]);
    }
}