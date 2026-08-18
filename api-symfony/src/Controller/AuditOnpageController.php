<?php

namespace App\Controller;

use App\Entity\Audit;
use App\Entity\AuditKeywordDensity;
use App\Entity\AuditPage;
use App\Entity\AuditPageHeading;
use App\Entity\AuditPageImage;
use App\Entity\Site;
use App\Repository\AuditRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AuditOnpageController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SiteRepository $siteRepository,
        private AuditRepository $auditRepository,
    ) {
    }

    // =========================================================
    // NORMALIZE URL
    // =========================================================

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        $parts = parse_url($url);

        if (!$parts || empty($parts['host'])) {
            return strtolower(rtrim($url, '/'));
        }

        $host = strtolower($parts['host']);
        $host = preg_replace('/^www\./i', '', $host);

        $path = $parts['path'] ?? '';
        $path = rtrim($path, '/');

        return $host . $path;
    }

    // =========================================================
    // FIND USER SITE
    // =========================================================

    private function findUserSite(
        string $url,
        $user
    ): ?Site {
        $sites = $this->siteRepository->findBy([
            'account' => $user
        ]);

        $normalizedInput = $this->normalizeUrl($url);

        foreach ($sites as $site) {
            if (
                $this->normalizeUrl($site->getUrl())
                === $normalizedInput
            ) {
                return $site;
            }
        }

        return null;
    }

    // =========================================================
    // CALCULATE ON-PAGE SCORE
    // =========================================================

    private function calculateOnpageScore(array $pageData): int
    {
        $checks = [];

        // -----------------------------------------------------
        // TITLE
        // -----------------------------------------------------

        $titleLength = (int) ($pageData['title_length'] ?? 0);

        $checks[] = (
            $titleLength >= 30 &&
            $titleLength <= 65
        );

        // -----------------------------------------------------
        // META DESCRIPTION
        // -----------------------------------------------------

        $metaLength = (int) ($pageData['meta_length'] ?? 0);

        $checks[] = (
            $metaLength >= 120 &&
            $metaLength <= 170
        );

        // -----------------------------------------------------
        // CANONICAL
        // -----------------------------------------------------

        $checks[] = !empty(
            $pageData['canonical_url']
        );

        // -----------------------------------------------------
        // ROBOTS
        // -----------------------------------------------------

        $checks[] = !empty(
            $pageData['meta_robots']
        );

        // -----------------------------------------------------
        // LANGUAGE
        // -----------------------------------------------------

        $checks[] = !empty(
            $pageData['lang_attribute']
        );

        // -----------------------------------------------------
        // H1
        // -----------------------------------------------------

        $h1Count = (int) (
            $pageData['h1_count'] ?? 0
        );

        $h1Unique = (bool) (
            $pageData['h1_is_unique'] ?? false
        );

        $checks[] = (
            $h1Count === 1 &&
            $h1Unique
        );

        // -----------------------------------------------------
        // WORD COUNT
        // -----------------------------------------------------

        $wordCount = (int) (
            $pageData['word_count'] ?? 0
        );

        $checks[] = $wordCount >= 300;

        // -----------------------------------------------------
        // IMAGES ALT
        // -----------------------------------------------------

        $imagesCount = (int) (
            $pageData['images_count'] ?? 0
        );

        $imagesWithoutAlt = (int) (
            $pageData['images_without_alt_count'] ?? 0
        );

        if ($imagesCount === 0) {
            $checks[] = true;
        } else {
            $checks[] = (
                $imagesWithoutAlt === 0
            );
        }

        // -----------------------------------------------------
        // STRUCTURED DATA
        // -----------------------------------------------------

        $checks[] = (
            (bool) (
                $pageData['has_structured_data']
                ?? false
            )
        );

        // -----------------------------------------------------
        // VIEWPORT
        // -----------------------------------------------------

        $checks[] = (
            (bool) (
                $pageData['viewport_meta']
                ?? false
            )
        );

        // -----------------------------------------------------
        // HTTPS
        // -----------------------------------------------------

        $checks[] = (
            (bool) (
                $pageData['is_https']
                ?? false
            )
        );

        // -----------------------------------------------------
        // RESPONSE TIME
        // -----------------------------------------------------

        $responseTime = $pageData[
            'response_time_ms'
        ] ?? null;

        if ($responseTime !== null) {
            $checks[] = (
                (int) $responseTime <= 1500
            );
        } else {
            $checks[] = false;
        }

        // -----------------------------------------------------
        // INTERNAL LINKS
        // -----------------------------------------------------

        $internalLinks = (int) (
            $pageData['internal_links_count']
            ?? 0
        );

        $checks[] = $internalLinks > 0;

        // -----------------------------------------------------
        // CALCUL
        // -----------------------------------------------------

        if (count($checks) === 0) {
            return 0;
        }

        $passed = count(
            array_filter(
                $checks,
                fn ($check) => $check === true
            )
        );

        return (int) round(
            ($passed / count($checks)) * 100
        );
    }

    // =========================================================
    // SCORE COLOR
    // =========================================================

    private function getScoreColor(int $score): string
    {
        if ($score >= 80) {
            return 'green';
        }

        if ($score >= 50) {
            return 'amber';
        }

        return 'red';
    }

    // =========================================================
    // MAIN ENDPOINT
    //
    // POST /api/audit-onpage
    // =========================================================

    #[Route(
        '/api/audit-onpage',
        name: 'api_audit_onpage',
        methods: ['POST']
    )]
    public function analyze(
        Request $request,
        #[CurrentUser] $user
    ): JsonResponse {

        // =====================================================
        // AUTH
        // =====================================================

        if (!$user) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Utilisateur non authentifié.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // =====================================================
        // JSON
        // =====================================================

        $data = json_decode(
            $request->getContent(),
            true
        );

        if (!is_array($data)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'JSON invalide.'
            ], Response::HTTP_BAD_REQUEST);
        }

        // =====================================================
        // URL
        // =====================================================

        $url = trim(
            $data['url']
            ?? $data['site_url']
            ?? ''
        );

        if ($url === '') {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'URL du site obligatoire.'
            ], Response::HTTP_BAD_REQUEST);
        }

        // =====================================================
        // SITE
        // =====================================================

        $site = $this->findUserSite(
            $url,
            $user
        );

        if (!$site) {
            return new JsonResponse([
                'status' => 'error',
                'message' =>
                    'Ce site n’existe pas ou ne vous appartient pas.'
            ], Response::HTTP_FORBIDDEN);
        }

        // =====================================================
        // CALL PYTHON
        // =====================================================

        $client = HttpClient::create();

        try {
            $response = $client->request(
                'GET',
                'http://analyzer:8000/audit-onpage',
                [
                    'query' => [
                        'url' => $site->getUrl()
                    ],
                    'timeout' => 120,
                ]
            );

            $pythonResult =
                $response->toArray(false);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' =>
                    'Impossible de contacter le Python Analyzer.',
                'details' => $e->getMessage()
            ], Response::HTTP_BAD_GATEWAY);
        }

        // =====================================================
        // PYTHON ERROR
        // =====================================================

        if (
            !isset($pythonResult['status']) ||
            $pythonResult['status'] !== 'success'
        ) {
            return new JsonResponse([
                'status' => 'error',
                'message' =>
                    $pythonResult['error_message']
                    ?? 'Analyse Python échouée.',
                'data' => $pythonResult
            ], Response::HTTP_BAD_REQUEST);
        }

        // =====================================================
        // PAGE DATA
        // =====================================================

        $pageData =
            $pythonResult['page']
            ?? [];

        // =====================================================
        // SCORE
        // =====================================================

        $globalScore = $this->calculateOnpageScore(
            $pageData
        );

        $scoreColor = $this->getScoreColor(
            $globalScore
        );

        // =====================================================
        // CREATE AUDIT
        // =====================================================

        $audit = new Audit();

        $audit->setSite($site);

        if (method_exists($audit, 'setCreatedAt')) {
            $audit->setCreatedAt(
                new \DateTimeImmutable()
            );
        }

        if (method_exists($audit, 'setStatus')) {
            $audit->setStatus('completed');
        }

        if (method_exists($audit, 'setGlobalScore')) {
            $audit->setGlobalScore(
                $globalScore
            );
        }

        if (method_exists($audit, 'setScoreColor')) {
            $audit->setScoreColor(
                $scoreColor
            );
        }

        if (method_exists($audit, 'setIsHttps')) {
            $audit->setIsHttps(
                $pageData['is_https'] ?? null
            );
        }

        $this->em->persist($audit);

        // =====================================================
        // AUDIT PAGE
        // =====================================================

        $auditPage = new AuditPage();

        $auditPage->setAudit($audit);

        if (
            array_key_exists('url', $pageData) &&
            method_exists($auditPage, 'setUrl')
        ) {
            $auditPage->setUrl(
                $pageData['url']
            );
        }

        if (
            array_key_exists('status_code', $pageData) &&
            method_exists($auditPage, 'setStatusCode')
        ) {
            $auditPage->setStatusCode(
                $pageData['status_code']
            );
        }

        if (
            array_key_exists('title', $pageData) &&
            method_exists($auditPage, 'setTitle')
        ) {
            $auditPage->setTitle(
                $pageData['title']
            );
        }

        if (
            array_key_exists('title_length', $pageData) &&
            method_exists($auditPage, 'setTitleLength')
        ) {
            $auditPage->setTitleLength(
                $pageData['title_length']
            );
        }

        if (
            array_key_exists('meta_description', $pageData) &&
            method_exists($auditPage, 'setMetaDescription')
        ) {
            $auditPage->setMetaDescription(
                $pageData['meta_description']
            );
        }

        if (
            array_key_exists('meta_length', $pageData) &&
            method_exists($auditPage, 'setMetaLength')
        ) {
            $auditPage->setMetaLength(
                $pageData['meta_length']
            );
        }

        if (
            array_key_exists('canonical_url', $pageData) &&
            method_exists($auditPage, 'setCanonicalUrl')
        ) {
            $auditPage->setCanonicalUrl(
                $pageData['canonical_url']
            );
        }

        if (
            array_key_exists('meta_robots', $pageData) &&
            method_exists($auditPage, 'setMetaRobots')
        ) {
            $auditPage->setMetaRobots(
                $pageData['meta_robots']
            );
        }

        if (
            array_key_exists('lang_attribute', $pageData) &&
            method_exists($auditPage, 'setLangAttribute')
        ) {
            $auditPage->setLangAttribute(
                $pageData['lang_attribute']
            );
        }

        if (
            array_key_exists('h1_count', $pageData) &&
            method_exists($auditPage, 'setH1Count')
        ) {
            $auditPage->setH1Count(
                $pageData['h1_count']
            );
        }

        if (
            array_key_exists('h1_is_unique', $pageData) &&
            method_exists($auditPage, 'setH1IsUnique')
        ) {
            $auditPage->setH1IsUnique(
                $pageData['h1_is_unique']
            );
        }

        if (
            array_key_exists('word_count', $pageData) &&
            method_exists($auditPage, 'setWordCount')
        ) {
            $auditPage->setWordCount(
                $pageData['word_count']
            );
        }

        if (
            array_key_exists('internal_links_count', $pageData) &&
            method_exists($auditPage, 'setInternalLinksCount')
        ) {
            $auditPage->setInternalLinksCount(
                $pageData['internal_links_count']
            );
        }

        if (
            array_key_exists('external_links_count', $pageData) &&
            method_exists($auditPage, 'setExternalLinksCount')
        ) {
            $auditPage->setExternalLinksCount(
                $pageData['external_links_count']
            );
        }

        if (
            array_key_exists('broken_links_count', $pageData) &&
            method_exists($auditPage, 'setBrokenLinksCount')
        ) {
            $auditPage->setBrokenLinksCount(
                $pageData['broken_links_count']
            );
        }

        if (
            array_key_exists('images_count', $pageData) &&
            method_exists($auditPage, 'setImagesCount')
        ) {
            $auditPage->setImagesCount(
                $pageData['images_count']
            );
        }

        if (
            array_key_exists('images_without_alt_count', $pageData) &&
            method_exists($auditPage, 'setImagesWithoutAltCount')
        ) {
            $auditPage->setImagesWithoutAltCount(
                $pageData['images_without_alt_count']
            );
        }

        if (
            array_key_exists('has_structured_data', $pageData) &&
            method_exists($auditPage, 'setHasStructuredData')
        ) {
            $auditPage->setHasStructuredData(
                $pageData['has_structured_data']
            );
        }

        if (
            array_key_exists('viewport_meta', $pageData) &&
            method_exists($auditPage, 'setViewportMeta')
        ) {
            $auditPage->setViewportMeta(
                $pageData['viewport_meta']
            );
        }

        if (
            array_key_exists('is_https', $pageData) &&
            method_exists($auditPage, 'setIsHttps')
        ) {
            $auditPage->setIsHttps(
                $pageData['is_https']
            );
        }

        if (
            array_key_exists('response_time_ms', $pageData) &&
            method_exists($auditPage, 'setResponseTimeMs')
        ) {
            $auditPage->setResponseTimeMs(
                $pageData['response_time_ms']
            );
        }

        if (
            array_key_exists('load_time_ms', $pageData) &&
            method_exists($auditPage, 'setLoadTimeMs')
        ) {
            $auditPage->setLoadTimeMs(
                $pageData['load_time_ms']
            );
        }

        if (
            array_key_exists('crawl_depth', $pageData) &&
            method_exists($auditPage, 'setCrawlDepth')
        ) {
            $auditPage->setCrawlDepth(
                $pageData['crawl_depth']
            );
        }

        if (
            array_key_exists('created_at', $pageData) &&
            method_exists($auditPage, 'setCreatedAt') &&
            !empty($pageData['created_at'])
        ) {
            try {
                $auditPage->setCreatedAt(
                    new \DateTimeImmutable(
                        $pageData['created_at']
                    )
                );
            } catch (\Throwable) {
                $auditPage->setCreatedAt(
                    new \DateTimeImmutable()
                );
            }
        }

        $this->em->persist($auditPage);

        // =====================================================
        // HEADINGS
        // =====================================================

        $headings =
            $pythonResult['headings']
            ?? [];

        foreach ($headings as $headingData) {

            $heading = new AuditPageHeading();

            $heading->setAuditPage(
                $auditPage
            );

            if (
                array_key_exists('heading_level', $headingData) &&
                method_exists($heading, 'setHeadingLevel')
            ) {
                $heading->setHeadingLevel(
                    $headingData['heading_level']
                );
            }

            if (
                array_key_exists('content', $headingData) &&
                method_exists($heading, 'setContent')
            ) {
                $heading->setContent(
                    $headingData['content']
                );
            }

            if (
                array_key_exists('position', $headingData) &&
                method_exists($heading, 'setPosition')
            ) {
                $heading->setPosition(
                    $headingData['position']
                );
            }

            $this->em->persist($heading);
        }

        // =====================================================
        // IMAGES
        // =====================================================

        $images =
            $pythonResult['images']
            ?? [];

        foreach ($images as $imageData) {

            $image = new AuditPageImage();

            $image->setAuditPage(
                $auditPage
            );

            if (
                array_key_exists('image_url', $imageData) &&
                method_exists($image, 'setImageUrl')
            ) {
                $image->setImageUrl(
                    $imageData['image_url']
                );
            }

            if (
                array_key_exists('has_alt', $imageData) &&
                method_exists($image, 'setHasAlt')
            ) {
                $image->setHasAlt(
                    $imageData['has_alt']
                );
            }

            if (
                array_key_exists('alt_text', $imageData) &&
                method_exists($image, 'setAltText')
            ) {
                $image->setAltText(
                    $imageData['alt_text']
                );
            }

            if (
                array_key_exists('file_size_kb', $imageData) &&
                method_exists($image, 'setFileSizeKb')
            ) {
                $image->setFileSizeKb(
                    $imageData['file_size_kb']
                );
            }

            if (
                array_key_exists('image_type', $imageData) &&
                method_exists($image, 'setImageType')
            ) {
                $image->setImageType(
                    $imageData['image_type']
                );
            }

            $this->em->persist($image);
        }

        // =====================================================
        // KEYWORD DENSITY
        // =====================================================

        $keywords =
            $pythonResult['keyword_density']
            ?? [];

        foreach ($keywords as $keywordData) {

            $keywordDensity =
                new AuditKeywordDensity();

            $keywordDensity->setAuditPage(
                $auditPage
            );

            if (
                array_key_exists('keyword', $keywordData) &&
                method_exists($keywordDensity, 'setKeyword')
            ) {
                $keywordDensity->setKeyword(
                    $keywordData['keyword']
                );
            }

            if (
                array_key_exists('count', $keywordData) &&
                method_exists($keywordDensity, 'setCount')
            ) {
                $keywordDensity->setCount(
                    $keywordData['count']
                );
            }

            if (
                array_key_exists('density', $keywordData) &&
                method_exists($keywordDensity, 'setDensity')
            ) {
                $keywordDensity->setDensity(
                    $keywordData['density']
                );
            }

            if (
                array_key_exists('percentage', $keywordData) &&
                method_exists($keywordDensity, 'setPercentage')
            ) {
                $keywordDensity->setPercentage(
                    $keywordData['percentage']
                );
            }

            $this->em->persist(
                $keywordDensity
            );
        }

        // =====================================================
        // SAVE
        // =====================================================

        try {

            $this->em->flush();

        } catch (\Throwable $e) {

            return new JsonResponse([
                'status' => 'error',
                'message' =>
                    'Erreur lors de la sauvegarde en base de données.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // =====================================================
        // RESPONSE
        // =====================================================

        return new JsonResponse([
            'status' => 'success',

            'message' =>
                'Analyse on-page terminée et enregistrée.',

            'data' => [

                'audit_id' =>
                    $audit->getId(),

                'audit_page_id' =>
                    $auditPage->getId(),

                'site' =>
                    $site->getUrl(),

                'url' =>
                    $pageData['url']
                    ?? $site->getUrl(),

                // SCORE
                'score' =>
                    $globalScore,

                'score_color' =>
                    $scoreColor,

                // PAGE
                'page' =>
                    $pageData,

                // HEADINGS
                'headings' =>
                    $headings,

                // IMAGES
                'images' =>
                    $images,

                // KEYWORDS
                'keyword_density' =>
                    $keywords,

                // DATE
                'created_at' =>
                    $audit->getCreatedAt()
                        ?->format('Y-m-d H:i:s'),
            ]

        ], Response::HTTP_CREATED);
    }

    // =========================================================
    // HISTORY
    //
    // GET /api/audit-onpage/history
    // =========================================================

    #[Route(
        '/api/audit-onpage/history',
        name: 'api_audit_onpage_history',
        methods: ['GET']
    )]
    public function history(
        #[CurrentUser] $user
    ): JsonResponse {

        if (!$user) {

            return new JsonResponse([
                'status' => 'error',
                'message' =>
                    'Utilisateur non authentifié.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // =====================================================
        // GET USER AUDITS
        // =====================================================

        $audits = $this->auditRepository
            ->createQueryBuilder('a')
            ->join('a.site', 's')
            ->where('s.account = :user')
            ->setParameter('user', $user)
            ->orderBy(
                'a.createdAt',
                'DESC'
            )
            ->getQuery()
            ->getResult();

        $history = [];

        // =====================================================
        // FORMAT
        // =====================================================

        foreach ($audits as $audit) {

            $site = $audit->getSite();

            // -------------------------------------------------
            // IMPORTANT:
            // Audit possède getPages()
            // PAS getAuditPages()
            // -------------------------------------------------

            $pages = [];

            foreach (
                $audit->getPages()
                as $page
            ) {

                $pages[] = [

                    'id' =>
                        $page->getId(),

                    'url' =>
                        method_exists(
                            $page,
                            'getUrl'
                        )
                            ? $page->getUrl()
                            : null,

                    'status_code' =>
                        method_exists(
                            $page,
                            'getStatusCode'
                        )
                            ? $page->getStatusCode()
                            : null,

                    'title' =>
                        method_exists(
                            $page,
                            'getTitle'
                        )
                            ? $page->getTitle()
                            : null,

                    'title_length' =>
                        method_exists(
                            $page,
                            'getTitleLength'
                        )
                            ? $page->getTitleLength()
                            : null,

                    'meta_description' =>
                        method_exists(
                            $page,
                            'getMetaDescription'
                        )
                            ? $page->getMetaDescription()
                            : null,

                    'meta_length' =>
                        method_exists(
                            $page,
                            'getMetaLength'
                        )
                            ? $page->getMetaLength()
                            : null,

                    'canonical_url' =>
                        method_exists(
                            $page,
                            'getCanonicalUrl'
                        )
                            ? $page->getCanonicalUrl()
                            : null,

                    'meta_robots' =>
                        method_exists(
                            $page,
                            'getMetaRobots'
                        )
                            ? $page->getMetaRobots()
                            : null,

                    'lang_attribute' =>
                        method_exists(
                            $page,
                            'getLangAttribute'
                        )
                            ? $page->getLangAttribute()
                            : null,

                    'h1_count' =>
                        method_exists(
                            $page,
                            'getH1Count'
                        )
                            ? $page->getH1Count()
                            : null,

                    'h1_is_unique' =>
                        method_exists(
                            $page,
                            'getH1IsUnique'
                        )
                            ? $page->getH1IsUnique()
                            : null,

                    'word_count' =>
                        method_exists(
                            $page,
                            'getWordCount'
                        )
                            ? $page->getWordCount()
                            : null,

                    'internal_links_count' =>
                        method_exists(
                            $page,
                            'getInternalLinksCount'
                        )
                            ? $page->getInternalLinksCount()
                            : null,

                    'external_links_count' =>
                        method_exists(
                            $page,
                            'getExternalLinksCount'
                        )
                            ? $page->getExternalLinksCount()
                            : null,

                    'broken_links_count' =>
                        method_exists(
                            $page,
                            'getBrokenLinksCount'
                        )
                            ? $page->getBrokenLinksCount()
                            : null,

                    'images_count' =>
                        method_exists(
                            $page,
                            'getImagesCount'
                        )
                            ? $page->getImagesCount()
                            : null,

                    'images_without_alt_count' =>
                        method_exists(
                            $page,
                            'getImagesWithoutAltCount'
                        )
                            ? $page->getImagesWithoutAltCount()
                            : null,

                    'has_structured_data' =>
                        method_exists(
                            $page,
                            'getHasStructuredData'
                        )
                            ? $page->getHasStructuredData()
                            : null,

                    'viewport_meta' =>
                        method_exists(
                            $page,
                            'getViewportMeta'
                        )
                            ? $page->getViewportMeta()
                            : null,

                    'is_https' =>
                        method_exists(
                            $page,
                            'getIsHttps'
                        )
                            ? $page->getIsHttps()
                            : null,

                    'response_time_ms' =>
                        method_exists(
                            $page,
                            'getResponseTimeMs'
                        )
                            ? $page->getResponseTimeMs()
                            : null,

                    'load_time_ms' =>
                        method_exists(
                            $page,
                            'getLoadTimeMs'
                        )
                            ? $page->getLoadTimeMs()
                            : null,

                    'crawl_depth' =>
                        method_exists(
                            $page,
                            'getCrawlDepth'
                        )
                            ? $page->getCrawlDepth()
                            : null,
                ];
            }

            // -------------------------------------------------
            // HISTORY ITEM
            // -------------------------------------------------

            $history[] = [

                'id' =>
                    $audit->getId(),

                'audit_id' =>
                    $audit->getId(),

                'site' =>
                    $site?->getUrl(),

                'url' =>
                    !empty($pages)
                        ? $pages[0]['url']
                        : $site?->getUrl(),

                // SCORE
                'score' =>
                    method_exists(
                        $audit,
                        'getGlobalScore'
                    )
                        ? $audit->getGlobalScore()
                        : null,

                'score_color' =>
                    method_exists(
                        $audit,
                        'getScoreColor'
                    )
                        ? $audit->getScoreColor()
                        : null,

                // STATUS
                'status' =>
                    method_exists(
                        $audit,
                        'getStatus'
                    )
                        ? $audit->getStatus()
                        : 'completed',

                // DATE
                'created_at' =>
                    $audit->getCreatedAt()
                        ?->format(
                            'Y-m-d H:i:s'
                        ),

                // PAGES
                'pages' =>
                    $pages,

                'pages_count' =>
                    count($pages),

                // REPORT URL
                'report_url' =>
                    '/analyse/' . $audit->getId(),
            ];
        }

        // =====================================================
        // RESPONSE
        // =====================================================

        return new JsonResponse([

            'status' =>
                'success',

            'count' =>
                count($history),

            'data' =>
                $history,

        ], Response::HTTP_OK);
    }

    // =========================================================
    // GET ONE REPORT
    //
    // GET /api/audit-onpage/{id}
    //
    // Kayrje3 rapport complet
    // =========================================================

    #[Route(
        '/api/audit-onpage/{id}',
        name: 'api_audit_onpage_report',
        methods: ['GET']
    )]
    public function report(
        int $id,
        #[CurrentUser] $user
    ): JsonResponse {

        if (!$user) {

            return new JsonResponse([
                'status' => 'error',
                'message' =>
                    'Utilisateur non authentifié.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // =====================================================
        // FIND AUDIT
        // =====================================================

        $audit = $this->auditRepository->find($id);

        if (!$audit) {

            return new JsonResponse([
                'status' => 'error',
                'message' =>
                    'Analyse introuvable.'
            ], Response::HTTP_NOT_FOUND);
        }

        // =====================================================
        // SECURITY
        // =====================================================

        $site = $audit->getSite();

        if (
            !$site ||
            !$site->getAccount() ||
            $site->getAccount()->getId()
                !== $user->getId()
        ) {

            return new JsonResponse([
                'status' => 'error',
                'message' =>
                    'Vous n’avez pas accès à cette analyse.'
            ], Response::HTTP_FORBIDDEN);
        }

        // =====================================================
        // PAGES
        // =====================================================

        $pages = [];

        foreach (
            $audit->getPages()
            as $page
        ) {

            $pages[] = [

                'id' =>
                    $page->getId(),

                'url' =>
                    method_exists(
                        $page,
                        'getUrl'
                    )
                        ? $page->getUrl()
                        : null,

                'status_code' =>
                    method_exists(
                        $page,
                        'getStatusCode'
                    )
                        ? $page->getStatusCode()
                        : null,

                'title' =>
                    method_exists(
                        $page,
                        'getTitle'
                    )
                        ? $page->getTitle()
                        : null,

                'title_length' =>
                    method_exists(
                        $page,
                        'getTitleLength'
                    )
                        ? $page->getTitleLength()
                        : null,

                'meta_description' =>
                    method_exists(
                        $page,
                        'getMetaDescription'
                    )
                        ? $page->getMetaDescription()
                        : null,

                'meta_length' =>
                    method_exists(
                        $page,
                        'getMetaLength'
                    )
                        ? $page->getMetaLength()
                        : null,

                'canonical_url' =>
                    method_exists(
                        $page,
                        'getCanonicalUrl'
                    )
                        ? $page->getCanonicalUrl()
                        : null,

                'meta_robots' =>
                    method_exists(
                        $page,
                        'getMetaRobots'
                    )
                        ? $page->getMetaRobots()
                        : null,

                'lang_attribute' =>
                    method_exists(
                        $page,
                        'getLangAttribute'
                    )
                        ? $page->getLangAttribute()
                        : null,

                'h1_count' =>
                    method_exists(
                        $page,
                        'getH1Count'
                    )
                        ? $page->getH1Count()
                        : null,

                'h1_is_unique' =>
                    method_exists(
                        $page,
                        'getH1IsUnique'
                    )
                        ? $page->getH1IsUnique()
                        : null,

                'word_count' =>
                    method_exists(
                        $page,
                        'getWordCount'
                    )
                        ? $page->getWordCount()
                        : null,

                'internal_links_count' =>
                    method_exists(
                        $page,
                        'getInternalLinksCount'
                    )
                        ? $page->getInternalLinksCount()
                        : null,

                'external_links_count' =>
                    method_exists(
                        $page,
                        'getExternalLinksCount'
                    )
                        ? $page->getExternalLinksCount()
                        : null,

                'images_count' =>
                    method_exists(
                        $page,
                        'getImagesCount'
                    )
                        ? $page->getImagesCount()
                        : null,

                'images_without_alt_count' =>
                    method_exists(
                        $page,
                        'getImagesWithoutAltCount'
                    )
                        ? $page->getImagesWithoutAltCount()
                        : null,

                'has_structured_data' =>
                    method_exists(
                        $page,
                        'getHasStructuredData'
                    )
                        ? $page->getHasStructuredData()
                        : null,

                'viewport_meta' =>
                    method_exists(
                        $page,
                        'getViewportMeta'
                    )
                        ? $page->getViewportMeta()
                        : null,

                'is_https' =>
                    method_exists(
                        $page,
                        'getIsHttps'
                    )
                        ? $page->getIsHttps()
                        : null,

                'response_time_ms' =>
                    method_exists(
                        $page,
                        'getResponseTimeMs'
                    )
                        ? $page->getResponseTimeMs()
                        : null,
            ];
        }

        // =====================================================
        // RESPONSE REPORT
        // =====================================================

        return new JsonResponse([

            'status' =>
                'success',

            'data' => [

                'audit_id' =>
                    $audit->getId(),

                'site' =>
                    $site->getUrl(),

                'score' =>
                    $audit->getGlobalScore(),

                'score_color' =>
                    $audit->getScoreColor(),

                'status' =>
                    $audit->getStatus(),

                'created_at' =>
                    $audit->getCreatedAt()
                        ?->format(
                            'Y-m-d H:i:s'
                        ),

                'pages' =>
                    $pages,

                'pages_count' =>
                    count($pages),
            ]

        ], Response::HTTP_OK);
    }
}