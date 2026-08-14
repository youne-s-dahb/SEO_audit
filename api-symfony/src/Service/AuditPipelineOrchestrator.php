<?php

namespace App\Service;

// Ce service est un chef d'orchestre (Pipeline) complet qui reçoit une URL, demande son analyse à un microservice Python, 
// récupère le résultat dans Redis, enregistre toutes les métriques SEO en base de données (4 tables), puis déclenche la 
// création du rapport PDF.

use App\Entity\Audit;
use App\Entity\AuditKeywordDensity;
use App\Entity\AuditPage;
use App\Entity\AuditPageHeading;
use App\Entity\AuditPageImage;
use App\Entity\Site;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Predis\Client as RedisClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AuditPipelineOrchestrator
{
    // Timeout genereux pour l'appel Python (scraping + fallback JS Playwright)
    private const PYTHON_CALL_TIMEOUT = 30;

    // ID utilisateur par defaut proprietaire des sites crees automatiquement
    private const DEFAULT_OWNER_USER_ID = 2;

    public function __construct(
        private EntityManagerInterface $em,
        private RedisClient $redis,
        private HttpClientInterface $httpClient,
        private AuditReportGenerator $reportGenerator,
        private string $pythonAnalyzerBaseUrl, // ex: http://analyzer:8000
    ) {
    }

    /**
     * Pipeline complet: URL -> Site/Audit -> scraping Python -> import
     * des 4 tables -> generation PDF. Arrete tout au premier probleme
     * rencontre (pas de rapport partiel).
     *
     * @return array{report: \App\Entity\AuditReport, audit: Audit}
     * @throws \RuntimeException si une etape echoue
     */
    public function run(string $url): array
    {
        $audit = $this->findOrCreateAudit($url);

        $payload = $this->fetchOnPageData($url);

        $auditPage = $this->importPage($audit, $payload, $url);
        $this->importHeadings($auditPage, $payload);
        $this->importImages($auditPage, $payload);
        $this->importKeywordDensity($auditPage, $payload);

        $audit->setStatus('completed');

        // Un seul flush centralise pour valider toutes les insertions et la mise a jour du statut
        $this->em->flush();

        $report = $this->reportGenerator->generate($audit);

        return ['report' => $report, 'audit' => $audit];
    }

    // --------------------------------------------------
    // 1. Site + Audit
    // --------------------------------------------------

    private function findOrCreateAudit(string $url): Audit
    {
        $site = $this->em->getRepository(Site::class)->findOneBy(['url' => $url]);

        if (!$site) {
            $owner = $this->em->getRepository(User::class)->find(self::DEFAULT_OWNER_USER_ID);

            if (!$owner) {
                throw new \RuntimeException('Default owner user not found. Cannot create Site.');
            }

            $site = new Site();
            $site->setUrl($url);
            $site->setNormalizedUrl(strtolower(rtrim($url, '/')));
            $site->setName((string) parse_url($url, PHP_URL_HOST));
            $site->setCountryCode('MA');
            $site->setLanguageCode('fr');
            $site->setAccount($owner);

            $this->em->persist($site);
        }

        $audit = new Audit();
        $audit->setSite($site);
        $audit->setRequestedBy($site->getAccount());
        $audit->setStatus('pending');
        $audit->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($audit);

        return $audit;
    }

    // --------------------------------------------------
    // 2. Appeler Python (scraping + fallback JS) et lire Redis
    // --------------------------------------------------

    private function fetchOnPageData(string $url): array
    {
        try {
            $response = $this->httpClient->request('GET', rtrim($this->pythonAnalyzerBaseUrl, '/') . '/audit-onpage', [
                'query' => ['url' => $url],
                'timeout' => self::PYTHON_CALL_TIMEOUT,
            ]);

            // On force la lecture du contenu ici pour bien capter les erreurs HTTP
            $response->getContent(false);

        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to reach the Python analyzer service: ' . $e->getMessage());
        }

        $redisKey = 'audit:onpage:' . $url;
        $cached = $this->redis->get($redisKey);

        if (!$cached) {
            throw new \RuntimeException('No on-page data found in Redis after scraping attempt.');
        }

        // Nettoyage de la cle Redis une fois recuperee
        $this->redis->del($redisKey);

        $payload = json_decode($cached, true);

        if (($payload['status'] ?? null) !== 'success') {
            $error = $payload['error_message'] ?? $payload['error'] ?? 'Unknown scraping error';
            throw new \RuntimeException('On-page scraping failed: ' . $error);
        }

        return $payload;
    }

    // --------------------------------------------------
    // 3. Import audit_pages
    // --------------------------------------------------

    private function importPage(Audit $audit, array $payload, string $url): AuditPage
    {
        $pageData = $payload['page'] ?? [];

        $auditPage = new AuditPage();
        $auditPage->setAudit($audit);
        $auditPage->setUrl($pageData['url'] ?? $url);
        $auditPage->setStatusCode(isset($pageData['status_code']) ? (int) $pageData['status_code'] : null);
        $auditPage->setTitle($pageData['title'] ?? null);
        $auditPage->setTitleLength(isset($pageData['title_length']) ? (int) $pageData['title_length'] : null);
        $auditPage->setMetaDescription($pageData['meta_description'] ?? null);
        $auditPage->setMetaLength(isset($pageData['meta_length']) ? (int) $pageData['meta_length'] : null);
        $auditPage->setCanonicalUrl($pageData['canonical_url'] ?? null);
        $auditPage->setMetaRobots($pageData['meta_robots'] ?? null);
        $auditPage->setLangAttribute($pageData['lang_attribute'] ?? null);
        $auditPage->setH1Count(isset($pageData['h1_count']) ? (int) $pageData['h1_count'] : null);
        $auditPage->setH1IsUnique(isset($pageData['h1_is_unique']) ? (bool) $pageData['h1_is_unique'] : null);
        $auditPage->setWordCount(isset($pageData['word_count']) ? (int) $pageData['word_count'] : null);
        $auditPage->setInternalLinksCount(isset($pageData['internal_links_count']) ? (int) $pageData['internal_links_count'] : null);
        $auditPage->setExternalLinksCount(isset($pageData['external_links_count']) ? (int) $pageData['external_links_count'] : null);
        $auditPage->setBrokenLinksCount(isset($pageData['broken_links_count']) ? (int) $pageData['broken_links_count'] : null);
        $auditPage->setImagesCount((int) ($pageData['images_count'] ?? 0));
        $auditPage->setImagesWithoutAltCount((int) ($pageData['images_without_alt_count'] ?? 0));
        $auditPage->setHasStructuredData((bool) ($pageData['has_structured_data'] ?? false));
        $auditPage->setCrawlDepth((int) ($pageData['crawl_depth'] ?? 0));
        $auditPage->setViewportMeta($pageData['viewport_meta'] ?? null);
        $auditPage->setIsHttps(isset($pageData['is_https']) ? (bool) $pageData['is_https'] : null);
        $auditPage->setResponseTimeMs(isset($pageData['response_time_ms']) ? (int) $pageData['response_time_ms'] : null);
        $auditPage->setLoadTimeMs(isset($pageData['load_time_ms']) ? (int) $pageData['load_time_ms'] : null);
        $auditPage->setCreatedAt(new \DateTimeImmutable());

        // FIX: synchroniser la collection en memoire cote Audit, sinon
        // $audit->getPages() reste vide dans cette meme requete PHP et
        // AuditReportGenerator::generate() leve "no pages to report on"
        // meme si tout est bien insere en base.
        $audit->addPage($auditPage);

        $this->em->persist($auditPage);

        return $auditPage;
    }

    // --------------------------------------------------
    // 4. Import audit_page_headings
    // --------------------------------------------------

    private function importHeadings(AuditPage $auditPage, array $payload): void
    {
        foreach ($payload['headings'] ?? [] as $item) {
            if (empty($item['heading_level']) || !isset($item['content'])) {
                continue;
            }

            $heading = new AuditPageHeading();
            $heading->setAuditPage($auditPage);
            $heading->setHeadingLevel((string) $item['heading_level']);
            $heading->setContent((string) $item['content']);
            $heading->setPosition(isset($item['position']) ? (int) $item['position'] : null);

            $this->em->persist($heading);
        }
    }

    // --------------------------------------------------
    // 5. Import audit_page_images
    // --------------------------------------------------

    private function importImages(AuditPage $auditPage, array $payload): void
    {
        foreach ($payload['images'] ?? [] as $item) {
            if (empty($item['image_url'])) {
                continue;
            }

            $image = new AuditPageImage();
            $image->setAuditPage($auditPage);
            $image->setImageUrl((string) $item['image_url']);
            $image->setHasAlt(isset($item['has_alt']) ? (bool) $item['has_alt'] : null);

            $altText = isset($item['alt_text']) ? trim((string) $item['alt_text']) : '';
            $image->setAltText($altText !== '' ? $altText : null);

            $image->setFileSizeKb(isset($item['file_size_kb']) ? (float) $item['file_size_kb'] : null);
            $image->setImageType(isset($item['image_type']) ? (string) $item['image_type'] : null);

            $this->em->persist($image);
        }
    }

    // --------------------------------------------------
    // 6. Import audit_keyword_density
    // --------------------------------------------------

    private function importKeywordDensity(AuditPage $auditPage, array $payload): void
    {
        foreach ($payload['keyword_density'] ?? [] as $item) {
            if (empty($item['keyword'])) {
                continue;
            }

            $keyword = new AuditKeywordDensity();
            $keyword->setAuditPage($auditPage);

            $cleanKeyword = trim((string) $item['keyword']);
            $keyword->setKeyword(mb_substr($cleanKeyword, 0, 255));

            $keyword->setOccurrences(isset($item['occurrences']) ? (int) $item['occurrences'] : null);
            $keyword->setDensityPercent(isset($item['density_percent']) ? (float) $item['density_percent'] : null);

            $this->em->persist($keyword);
        }
    }
}