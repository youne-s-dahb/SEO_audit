<?php

namespace App\Controller;

// Ce contrôleur expose un endpoint d'API qui reçoit une URL, lance l'orchestrateur d'audit complet, 
// puis retourne directement le rapport PDF à afficher ou télécharger dans le navigateur.

use App\Service\AuditPipelineOrchestrator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

class AuditPipelineController extends AbstractController
{
    #[Route('/api/audit-pipeline/run', name: 'audit_pipeline_run', methods: ['GET', 'POST'])]
    public function run(
        Request $request,
        AuditPipelineOrchestrator $orchestrator
    ): BinaryFileResponse|JsonResponse {

        // 1. Récupération de l'URL (GET query, POST body form, ou POST JSON)
        $url = $request->query->get('url') 
            ?? $request->request->get('url') 
            ?? $request->toArray()['url'] ?? null;

        if (!$url) {
            return $this->json(['error' => 'URL parameters is required.'], 400);
        }

        // 2. Validation du format de l'URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->json(['error' => 'Invalid URL format provided.'], 422);
        }

        // 3. Exécution du pipeline d'audit
        try {
            $result = $orchestrator->run($url);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Unexpected error during the audit pipeline.',
                'message' => $e->getMessage(),
            ], 500);
        }

        $filePath = $result['report']->getFilePath();

        if (!file_exists($filePath)) {
            return $this->json(['error' => 'Report file was not found on disk after generation.'], 500);
        }

        // 4. Préparation du PDF
        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            basename($filePath)
        );

        return $response;
    }
}