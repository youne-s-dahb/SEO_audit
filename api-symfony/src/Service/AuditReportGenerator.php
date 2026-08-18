<?php

namespace App\Service;

// Ce service Symfony génère un rapport PDF à partir des données d'un audit (via un template Twig et 
// Dompdf), enregistre le fichier sur le disque, puis sauvegarde sa référence en base de données.

use App\Entity\Audit;
use App\Entity\AuditReport;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class AuditReportGenerator
{
    // Removed 'readonly' keyword for PHP 8.0 compatibility
    public function __construct(
        private EntityManagerInterface $em,
        private Environment $twig,
        private string $reportsDirectory,
    ) {
    }

    /**
     * Generates a PDF report for a given Audit, saves it to disk,
     * and persists the corresponding AuditReport entity.
     *
     * @throws \RuntimeException if the audit has no pages or if file writing fails
     */
    public function generate(Audit $audit): AuditReport
    {
        $pages = $audit->getPages();

        if ($pages->isEmpty()) {
            throw new \RuntimeException('This audit has no pages to report on.');
        }

        // -----------------------------
        // 1. Render HTML via Twig
        // -----------------------------
        $now = new \DateTimeImmutable();
        $html = $this->twig->render('report/audit_report.html.twig', [
            'audit' => $audit,
            'pages' => $pages,
            'generated_at' => $now,
        ]);

        // -----------------------------
        // 2. Convert to PDF via Dompdf
        // -----------------------------
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');
        
        $options->set('chroot', [realpath($this->reportsDirectory) ?: $this->reportsDirectory]);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfContent = $dompdf->output();

        // -----------------------------
        // 3. Securely save file to disk
        // -----------------------------
        if (!is_dir($this->reportsDirectory) && !mkdir($this->reportsDirectory, 0775, true) && !is_dir($this->reportsDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" could not be created.', $this->reportsDirectory));
        }

        $filename = sprintf(
            'audit_%d_%s.pdf',
            $audit->getId(),
            $now->format('Ymd_His')
        );

        $filePath = rtrim($this->reportsDirectory, '/\\') . DIRECTORY_SEPARATOR . $filename;

        if (file_put_contents($filePath, $pdfContent) === false) {
            throw new \RuntimeException(sprintf('Failed to write PDF report to file: "%s"', $filePath));
        }

        // -----------------------------
        // 4. Create and persist AuditReport
        // -----------------------------
        $report = new AuditReport();
        $report->setAudit($audit);
        $report->setFormat('pdf');
        $report->setFilePath($filePath);
        $report->setGeneratedAt($now);

        $this->em->persist($report);
        $this->em->flush();

        return $report;
    }
}