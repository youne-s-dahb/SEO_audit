<?php

namespace App\Controller;

use App\Entity\Audit;
use App\Entity\AuditPage;
use App\Entity\AuditKeywordDensity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AuditKeywordDensityImportController extends AbstractController
{
    #[Route(
        '/api/audit-keyword-density/import',
        name: 'audit_keyword_density_import',
        methods: ['POST']
    )]
    public function __invoke(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {

        // -------------------------------------------------
        // 1. Décoder le JSON
        // -------------------------------------------------
        $payload = json_decode(
            $request->getContent(),
            true
        );

        if (!is_array($payload)) {
            return $this->json([
                'error' => 'Invalid JSON payload.'
            ], 400);
        }

        // -------------------------------------------------
        // 2. Vérifier les champs obligatoires
        // -------------------------------------------------
        if (
            empty($payload['url']) ||
            empty($payload['audit_id'])
        ) {
            return $this->json([
                'error' => 'url and audit_id are required fields.'
            ], 400);
        }

        // -------------------------------------------------
        // 3. Vérifier le status du scraping (AVANT les requêtes DB)
        // -------------------------------------------------
        $status = $payload['status'] ?? null;

        if (!$status) {
            return $this->json([
                'error' => 'On-page audit status missing in payload.'
            ], 400);
        }

        if ($status !== 'success') {
            return $this->json([
                'error' => 'On-page scraping failed for this URL.',
                'status' => $status
            ], 422);
        }

        // -------------------------------------------------
        // 4. Vérifier keyword_density array
        // -------------------------------------------------
        $keywordsData = $payload['keyword_density'] ?? [];

        if (!is_array($keywordsData)) {
            return $this->json([
                'error' => 'keyword_density must be an array.'
            ], 400);
        }

        // -------------------------------------------------
        // 5. Normaliser URL & audit_id
        // -------------------------------------------------
        $auditId = $payload['audit_id'];

        if (
            !is_int($auditId) &&
            !ctype_digit((string) $auditId)
        ) {
            return $this->json([
                'error' => 'audit_id must be a valid integer.'
            ], 400);
        }

        $auditId = (int) $auditId;

        $url = trim((string) $payload['url']);
        $normalizedUrl = rtrim($url, '/');

        if ($normalizedUrl === '') {
            $normalizedUrl = '/';
        }

        // -------------------------------------------------
        // 6. Récupérer l'Audit parent
        // -------------------------------------------------
        $audit = $em
            ->getRepository(Audit::class)
            ->find($auditId);

        if (!$audit) {
            return $this->json([
                'error' => "Audit with id {$auditId} not found."
            ], 404);
        }

        // -------------------------------------------------
        // 7. Récupérer l'AuditPage parent
        // -------------------------------------------------
        $auditPage = $em
            ->getRepository(AuditPage::class)
            ->findOneBy([
                'audit' => $audit,
                'url' => $url,
            ]);

        if (!$auditPage && $normalizedUrl !== $url) {
            $auditPage = $em
                ->getRepository(AuditPage::class)
                ->findOneBy([
                    'audit' => $audit,
                    'url' => $normalizedUrl,
                ]);
        }

        if (!$auditPage) {
            return $this->json([
                'error' => 'AuditPage not found for this url/audit.',
                'details' => 'Call /api/audit-page/import first.'
            ], 404);
        }

        // -------------------------------------------------
        // 8. Transaction DB
        // -------------------------------------------------
        $connection = $em->getConnection();

        try {
            $connection->beginTransaction();

            // -------------------------------------------------
            // 9. Supprimer les anciens mots-clés
            // -------------------------------------------------
            $em->getRepository(AuditKeywordDensity::class)
                ->createQueryBuilder('k')
                ->delete()
                ->where('k.auditPage = :auditPage')
                ->setParameter('auditPage', $auditPage)
                ->getQuery()
                ->execute();

            // -------------------------------------------------
            // 10. Ajouter les nouveaux mots-clés
            // -------------------------------------------------
            $insertedCount = 0;

            foreach ($keywordsData as $item) {

                if (
                    !is_array($item) ||
                    empty($item['keyword'])
                ) {
                    continue;
                }

                $keyword = new AuditKeywordDensity();
                $keyword->setAuditPage($auditPage);

                $cleanKeyword = trim((string) $item['keyword']);
                $keyword->setKeyword(mb_substr($cleanKeyword, 0, 255));

                if (
                    array_key_exists('occurrences', $item) &&
                    is_numeric($item['occurrences'])
                ) {
                    $keyword->setOccurrences((int) $item['occurrences']);
                } else {
                    $keyword->setOccurrences(null);
                }

                if (
                    array_key_exists('density_percent', $item) &&
                    is_numeric($item['density_percent'])
                ) {
                    $keyword->setDensityPercent((float) $item['density_percent']);
                } else {
                    $keyword->setDensityPercent(null);
                }

                $em->persist($keyword);
                $insertedCount++;
            }

            // -------------------------------------------------
            // 11. Sauvegarder
            // -------------------------------------------------
            $em->flush();

            // -------------------------------------------------
            // 12. Valider la transaction
            // -------------------------------------------------
            $connection->commit();

        } catch (\Throwable $e) {

            // -------------------------------------------------
            // 13. Rollback en cas d'erreur
            // -------------------------------------------------
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            return $this->json([
                'error' => 'Database error while saving keyword density.'
            ], 500);
        }

        // -------------------------------------------------
        // 14. Réponse
        // -------------------------------------------------
        return $this->json([
            'message' => 'AuditKeywordDensity stored successfully',
            'audit_page_id' => $auditPage->getId(),
            'keywords_count' => $insertedCount,
        ]);
    }
}