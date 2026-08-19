<?php

namespace App\Controller;

use App\Entity\Audit;
use App\Entity\AuditPage;
use App\Entity\AuditPageHeading;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AuditPageHeadingImportController extends AbstractController
{
    private const PYTHON_CALL_TIMEOUT = 30;

    private HttpClientInterface $httpClient;
    private string $pythonAnalyzerBaseUrl;
    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        string $pythonAnalyzerBaseUrl,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->pythonAnalyzerBaseUrl = $pythonAnalyzerBaseUrl;
        $this->logger = $logger;
    }

    #[Route('/api/audit-page-heading/import', name: 'audit_page_heading_import', methods: ['POST'])]
    public function __invoke(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {

        $payload = json_decode($request->getContent(), true);

        if (empty($payload['url']) || empty($payload['audit_id'])) {
            return $this->json([
                'error' => 'url and audit_id are required fields'
            ], 400);
        }

        $url = $payload['url'];
        $auditId = $payload['audit_id'];

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->json(['error' => 'Invalid URL format provided.'], 400);
        }

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

        // -----------------------------
        // Appel direct a Python
        // -----------------------------
        try {
            $response = $this->httpClient->request('GET', rtrim($this->pythonAnalyzerBaseUrl, '/') . '/audit-onpage', [
                'query' => ['url' => $url],
                'timeout' => self::PYTHON_CALL_TIMEOUT,
            ]);

            $data = $response->toArray(false);

        } catch (\Exception $e) {
            $this->logger->error('Failed to reach Python analyzer service', [
                'exception' => $e->getMessage(),
                'url' => $url
            ]);

            return $this->json([
                'error' => 'Failed to reach the Python analyzer service.',
            ], 502);
        }

        if (($data['status'] ?? null) !== 'success') {
            return $this->json([
                'error' => 'On-page scraping failed for this URL',
                'details' => $data,
            ], 422);
        }

        $headingsData = $data['headings'] ?? [];

        // Bulk delete des anciens headings de cette page
        $em->getRepository(AuditPageHeading::class)->createQueryBuilder('h')
            ->delete()
            ->where('h.auditPage = :auditPage')
            ->setParameter('auditPage', $auditPage)
            ->getQuery()
            ->execute();

        $insertedCount = 0;

        foreach ($headingsData as $item) {
            $level = trim($item['heading_level'] ?? '');
            $content = trim($item['content'] ?? '');

            // Verification plus stricte du contenu
            if ($level === '' || $content === '') {
                continue;
            }

            $heading = new AuditPageHeading();
            $heading->setAuditPage($auditPage);
            $heading->setHeadingLevel($level);
            $heading->setContent($content);
            $heading->setPosition(isset($item['position']) ? (int) $item['position'] : null);

            $em->persist($heading);
            $insertedCount++;
        }

        try {
            $em->flush();
        } catch (\Exception $e) {
            $this->logger->error('Database error while saving headings', [
                'exception' => $e->getMessage(),
                'audit_page_id' => $auditPage->getId()
            ]);

            return $this->json([
                'error' => 'Database error while saving headings.',
            ], 500);
        }

        return $this->json([
            'message' => 'AuditPageHeading stored successfully',
            'audit_page_id' => $auditPage->getId(),
            'headings_count' => $insertedCount,
        ]);
    }
}