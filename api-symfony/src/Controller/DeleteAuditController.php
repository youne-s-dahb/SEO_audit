<?php

namespace App\Controller;

use App\Entity\Audit;
use App\Entity\AuditPage;
use App\Entity\AuditKeywordDensity;
use App\Entity\AuditPageHeading;
use App\Entity\AuditPageImage;
use App\Entity\Recommendation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DeleteAuditController
{
    #[Route('/api/audits/{id}/delete', name: 'delete_audit_custom', methods: ['DELETE'])]
    public function __invoke(
        Audit $audit,
        EntityManagerInterface $entityManager
    ): Response {
        // 1. Supprimer les recommendations liées à l'audit
        $entityManager->createQueryBuilder()
            ->delete(Recommendation::class, 'r')
            ->where('r.audit = :audit')
            ->setParameter('audit', $audit)
            ->getQuery()
            ->execute();

        // 2. Récupérer les pages
        $pages = $entityManager->getRepository(AuditPage::class)
            ->findBy(['audit' => $audit]);

        foreach ($pages as $page) {

            // 3. Supprimer keyword density
            $entityManager->createQueryBuilder()
                ->delete(AuditKeywordDensity::class, 'k')
                ->where('k.auditPage = :page')
                ->setParameter('page', $page)
                ->getQuery()
                ->execute();

            // 4. Supprimer headings
            $entityManager->createQueryBuilder()
                ->delete(AuditPageHeading::class, 'h')
                ->where('h.auditPage = :page')
                ->setParameter('page', $page)
                ->getQuery()
                ->execute();

            // 5. Supprimer images
            $entityManager->createQueryBuilder()
                ->delete(AuditPageImage::class, 'i')
                ->where('i.auditPage = :page')
                ->setParameter('page', $page)
                ->getQuery()
                ->execute();

            // 6. Supprimer AuditPage
            $entityManager->remove($page);
        }

        // 7. Supprimer Audit
        $entityManager->remove($audit);

        $entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}