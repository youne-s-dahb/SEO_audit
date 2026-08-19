<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AuditOnpageController extends AbstractController
{
    // =========================================================
    // NORMALIZE URL
    // =========================================================

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        $parts = parse_url($url);

        if (!$parts || empty($parts['host'])) {
            return strtolower(
                rtrim(
                    preg_replace(
                        '#^https?://#i',
                        '',
                        $url
                    ),
                    '/'
                )
            );
        }

        $host = strtolower($parts['host']);

        // www.example.com == example.com
        $host = preg_replace('/^www\./i', '', $host);

        $path = $parts['path'] ?? '';
        $path = rtrim($path, '/');

        return $host . $path;
    }

    // =========================================================
    // CALCULATE ON-PAGE SCORE
    // =========================================================

    private function calculateOnpageScore(array $pageData): int
    {
        $checks = [];

        // TITLE
        $titleLength = (int) (
            $pageData['title_length'] ?? 0
        );

        $checks[] = (
            $titleLength >= 30 &&
            $titleLength <= 65
        );

        // META DESCRIPTION
        $metaLength = (int) (
            $pageData['meta_length'] ?? 0
        );

        $checks[] = (
            $metaLength >= 120 &&
            $metaLength <= 170
        );

        // CANONICAL
        $checks[] = !empty(
            $pageData['canonical_url']
        );

        // ROBOTS
        $checks[] = !empty(
            $pageData['meta_robots']
        );

        // LANGUAGE
        $checks[] = !empty(
            $pageData['lang_attribute']
        );

        // H1
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

        // WORD COUNT
        $wordCount = (int) (
            $pageData['word_count'] ?? 0
        );

        $checks[] = $wordCount >= 300;

        // IMAGES ALT
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

        // STRUCTURED DATA
        $checks[] = (
            (bool) (
                $pageData['has_structured_data'] ?? false
            )
        );

        // VIEWPORT
        $checks[] = (
            (bool) (
                $pageData['viewport_meta'] ?? false
            )
        );

        // HTTPS
        $checks[] = (
            (bool) (
                $pageData['is_https'] ?? false
            )
        );

        // RESPONSE TIME
        $responseTime = $pageData['response_time_ms'] ?? null;

        if ($responseTime !== null) {
            $checks[] = (
                (int) $responseTime <= 1500
            );
        } else {
            $checks[] = false;
        }

        // INTERNAL LINKS
        $internalLinks = (int) (
            $pageData['internal_links_count'] ?? 0
        );

        $checks[] = $internalLinks > 0;

        // CALCUL
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
    //
    // IMPORTANT:
    // AUCUNE DONNÉE N'EST ENREGISTRÉE EN BASE.
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
        // ADD HTTPS IF NECESSARY
        // =====================================================

        $cleanUrl = $url;

        if (!preg_match('#^https?://#i', $cleanUrl)) {
            $cleanUrl = 'https://' . $cleanUrl;
        }

        // =====================================================
        // VALIDATE URL
        // =====================================================

        if (
            !filter_var(
                $cleanUrl,
                FILTER_VALIDATE_URL
            )
        ) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'URL invalide.'
            ], Response::HTTP_BAD_REQUEST);
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
                        'url' => $cleanUrl
                    ],
                    'timeout' => 120,
                ]
            );

            $pythonResult = $response->toArray(false);

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

        $globalScore =
            $this->calculateOnpageScore(
                $pageData
            );

        $scoreColor =
            $this->getScoreColor(
                $globalScore
            );

        // =====================================================
        // DATA
        // =====================================================

        $headings =
            $pythonResult['headings']
            ?? [];

        $images =
            $pythonResult['images']
            ?? [];

        $keywords =
            $pythonResult['keyword_density']
            ?? [];

        // =====================================================
        // RESPONSE
        //
        // AUCUN persist()
        // AUCUN flush()
        // AUCUNE insertion DB
        // =====================================================

        return new JsonResponse([

            'status' => 'success',

            'message' =>
                'Analyse on-page terminée.',

            'data' => [

                // URL
                'site' =>
                    $cleanUrl,

                'url' =>
                    $pageData['url']
                    ?? $cleanUrl,

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

                // DATE TEMPORAIRE
                // uniquement pour afficher dans React
                // sans sauvegarder en DB
                'created_at' =>
                    (new \DateTimeImmutable())
                        ->format('Y-m-d H:i:s'),

            ]

        ], Response::HTTP_OK);
    }
}