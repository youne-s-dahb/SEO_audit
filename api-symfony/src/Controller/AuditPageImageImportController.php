<?php

namespace App\Controller;

use App\Entity\Audit;
use App\Entity\AuditPage;
use App\Entity\AuditPageImage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AuditPageImageImportController extends AbstractController
{
    #[Route(
        '/api/audit-page-image/import',
        name: 'audit_page_image_import',
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

        $url = trim((string) $payload['url']);
        $auditId = $payload['audit_id'];

        // -------------------------------------------------
        // 3. Vérifier audit_id
        // -------------------------------------------------
        if (
            !is_int($auditId) &&
            !ctype_digit((string) $auditId)
        ) {
            return $this->json([
                'error' => 'audit_id must be a valid integer.'
            ], 400);
        }

        $auditId = (int) $auditId;

        // -------------------------------------------------
        // 4. Normaliser l'URL
        // -------------------------------------------------
        $normalizedUrl = rtrim($url, '/');

        // Garder "/" pour la racine
        if ($normalizedUrl === '') {
            $normalizedUrl = '/';
        }

        // -------------------------------------------------
        // 5. Récupérer l'Audit parent
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
        // 6. Récupérer l'AuditPage parent
        // -------------------------------------------------
        $auditPage = $em
            ->getRepository(AuditPage::class)
            ->findOneBy([
                'audit' => $audit,
                'url' => $url,
            ]);

        // Si l'URL exacte n'est pas trouvée,
        // essayer avec l'URL normalisée.
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
        // 7. Vérifier le status du scraping
        // -------------------------------------------------
        $status = $payload['status'] ?? null;

        if (!$status) {
            return $this->json([
                'error' => 'On-page audit data missing in payload.'
            ], 400);
        }

        if ($status !== 'success') {
            return $this->json([
                'error' => 'On-page scraping failed for this URL.',
                'status' => $status
            ], 422);
        }

        // -------------------------------------------------
        // 8. Vérifier images
        // -------------------------------------------------
        $imagesData = $payload['images'] ?? [];

        if (!is_array($imagesData)) {
            return $this->json([
                'error' => 'images must be an array.'
            ], 400);
        }

        // -------------------------------------------------
        // 9. Transaction DB
        // -------------------------------------------------
        $connection = $em->getConnection();

        try {
            $connection->beginTransaction();

            // -------------------------------------------------
            // 10. Supprimer les anciennes images
            // -------------------------------------------------
            $em->getRepository(AuditPageImage::class)
                ->createQueryBuilder('i')
                ->delete()
                ->where('i.auditPage = :auditPage')
                ->setParameter('auditPage', $auditPage)
                ->getQuery()
                ->execute();

            // -------------------------------------------------
            // 11. Ajouter les nouvelles images
            // -------------------------------------------------
            $insertedCount = 0;

            foreach ($imagesData as $item) {

                // Ignorer les éléments invalides
                if (
                    !is_array($item) ||
                    empty($item['image_url'])
                ) {
                    continue;
                }

                $image = new AuditPageImage();

                $image->setAuditPage($auditPage);

                // URL de l'image
                $image->setImageUrl(
                    trim((string) $item['image_url'])
                );

                // Alt
                if (array_key_exists('has_alt', $item)) {
                    $image->setHasAlt(
                        (bool) $item['has_alt']
                    );
                } else {
                    $image->setHasAlt(null);
                }

                // Texte Alt
                if (
                    array_key_exists('alt_text', $item) &&
                    $item['alt_text'] !== null
                ) {
                    $image->setAltText(
                        (string) $item['alt_text']
                    );
                } else {
                    $image->setAltText(null);
                }

                // Taille
                if (
                    array_key_exists('file_size_kb', $item) &&
                    is_numeric($item['file_size_kb'])
                ) {
                    $image->setFileSizeKb(
                        (float) $item['file_size_kb']
                    );
                } else {
                    $image->setFileSizeKb(null);
                }

                // Type
                if (
                    array_key_exists('image_type', $item) &&
                    $item['image_type'] !== null
                ) {
                    $image->setImageType(
                        (string) $item['image_type']
                    );
                } else {
                    $image->setImageType(null);
                }

                $em->persist($image);

                $insertedCount++;
            }

            // -------------------------------------------------
            // 12. Sauvegarder
            // -------------------------------------------------
            $em->flush();

            // -------------------------------------------------
            // 13. Valider la transaction
            // -------------------------------------------------
            $connection->commit();

        } catch (\Throwable $e) {

            // -------------------------------------------------
            // 14. Rollback en cas d'erreur
            // -------------------------------------------------
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            // En développement, tu peux logger l'erreur.
            // Ne pas exposer $e->getMessage() directement à l'utilisateur.
            return $this->json([
                'error' => 'Database error while saving images.'
            ], 500);
        }

        // -------------------------------------------------
        // 15. Réponse
        // -------------------------------------------------
        return $this->json([
            'message' => 'AuditPageImage stored successfully',
            'audit_page_id' => $auditPage->getId(),
            'images_count' => $insertedCount,
        ]);
    }
}