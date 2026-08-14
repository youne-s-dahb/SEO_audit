<?php

namespace App\Controller;

// Ce contrôleur Symfony fournit deux API REST : une pour lancer la génération du rapport PDF d'un audit, 
//et une autre pour télécharger le fichier PDF déjà créé.

use App\Entity\Audit;
use App\Entity\AuditReport;
use App\Service\AuditReportGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

class AuditReportController extends AbstractController
{
    // -----------------------------
    // Generer le rapport PDF pour un audit
    // -----------------------------
    #[Route('/api/audit-report/generate/{auditId}', name: 'audit_report_generate', methods: ['GET', 'POST'])]
    public function generate(
        int $auditId,
        EntityManagerInterface $em,
        AuditReportGenerator $generator
    ): JsonResponse {

        $audit = $em->getRepository(Audit::class)->find($auditId);

        if (!$audit) {
            return $this->json(['error' => "Audit with id {$auditId} not found"], 404);
        }

        try {
            $report = $generator->generate($audit);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to generate PDF report.',
                'message' => $e->getMessage(),
            ], 500);
        }

        return $this->json([
            'message' => 'Report generated successfully',
            'report_id' => $report->getId(),
            'format' => $report->getFormat(),
            'generated_at' => $report->getGeneratedAt()->format(\DateTimeInterface::ATOM),
            'download_url' => "/api/audit-report/{$report->getId()}/download",
        ]);
    }

    // -----------------------------
    // Telecharger un rapport PDF deja genere
    // -----------------------------
    #[Route('/api/audit-report/{reportId}/download', name: 'audit_report_download', methods: ['GET'])]
    public function download(
        int $reportId,
        EntityManagerInterface $em
    ): BinaryFileResponse|JsonResponse {

        $report = $em->getRepository(AuditReport::class)->find($reportId);

        if (!$report) {
            return $this->json(['error' => "Report with id {$reportId} not found"], 404);
        }

        $filePath = $report->getFilePath();

        if (!file_exists($filePath)) {
            return $this->json([
                'error' => 'Report file not found on disk. It may have been deleted.',
                'file_path' => $filePath,
            ], 404);
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($filePath)
        );

        return $response;
    }
}