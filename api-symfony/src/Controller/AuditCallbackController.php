<?php

namespace App\Controller;

use App\Entity\Audit;
use App\Entity\Recommendation;
use App\Entity\Site;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AuditCallbackController extends AbstractController
{
    #[Route('/api/audits/run', name: 'run_audit', methods: ['POST'])]
    public function __invoke(
        Request $request,
        EntityManagerInterface $em,
        #[CurrentUser] ?User $user
    ): JsonResponse {

        /*
         * ==========================================
         * 1. Vérifier user connecté
         * ==========================================
         */

        if (!$user) {
            return $this->json([
                'error' => 'Machi connecté.'
            ], Response::HTTP_UNAUTHORIZED);
        }


        /*
         * ==========================================
         * 2. Lire le body JSON
         * ==========================================
         */

        $body = json_decode(
            $request->getContent(),
            true
        );

        if (!is_array($body)) {
            return $this->json([
                'error' => 'Invalid JSON.'
            ], Response::HTTP_BAD_REQUEST);
        }


        /*
         * ==========================================
         * 3. Vérifier URL
         * ==========================================
         */

        if (empty($body['url'])) {
            return $this->json([
                'error' => 'URL is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $url = trim($body['url']);


        /*
         * ==========================================
         * 4. Recherche du site
         * ==========================================
         *
         * Important:
         * Le site appartient au user connecté.
         */

        $site = $em
            ->getRepository(Site::class)
            ->findOneBy([
                'url' => $url,
                'account' => $user
            ]);


        /*
         * ==========================================
         * 5. Créer le site s'il n'existe pas
         * ==========================================
         */

        if (!$site) {

            $site = new Site();

            $site->setUrl($url);

            $site->setNormalizedUrl(
                strtolower(
                    rtrim($url, '/')
                )
            );

            $site->setName(
                parse_url(
                    $url,
                    PHP_URL_HOST
                ) ?: $url
            );

            $site->setCountryCode(
                $body['country_code'] ?? 'MA'
            );

            $site->setLanguageCode(
                $body['language_code'] ?? 'fr'
            );

            /*
             * IMPORTANT:
             * Le site appartient au user connecté.
             */
            $site->setAccount($user);

            $em->persist($site);

            /*
             * Flush pour garantir que le site
             * existe avant l'audit.
             */
            $em->flush();
        }


        /*
         * ==========================================
         * 6. Créer l'Audit
         * ==========================================
         */

        $audit = new Audit();

        $audit->setSite($site);

        /*
         * IMPORTANT:
         * L'audit appartient au user connecté.
         */
        $audit->setRequestedBy($user);

        $audit->setStatus('processing');

        $audit->setCreatedAt(
            new \DateTimeImmutable()
        );

        $em->persist($audit);

        $em->flush();


        /*
         * ==========================================
         * 7. Appeler Python Analyzer
         * ==========================================
         */

        $client = HttpClient::create([
            'timeout' => 150,
            'max_duration' => 150,
        ]);


        try {

            $response = $client->request(
                'GET',
                'http://analyzer:8000/audit',
                [
                    'query' => [
                        'url' => $url
                    ]
                ]
            );

            $data = $response->toArray();
        
        } catch (\Throwable $e) {

            /*
             * Si Python timeout / erreur
             */

            $audit->setStatus('failed');

            $audit->setErrorMessage(
                'Audit timeout: ' . $e->getMessage()
            );

            $em->flush();

            return $this->json([
                'error' =>
                    'L\'audit khda wa9t bzaf o waqef.',
                'audit_id' =>
                    $audit->getId(),
            ], Response::HTTP_GATEWAY_TIMEOUT);
        }


        /*
         * ==========================================
         * 8. Sauvegarder résultat audit
         * ==========================================
         */

        $audit->setStatus(
            $data['status'] ?? 'completed'
        );

        $audit->setGlobalScore(
            $data['global_score'] ?? null
        );

        $audit->setScoreColor(
            $data['score_color'] ?? null
        );


        /*
         * ==========================================
         * Technical SEO
         * ==========================================
         */

        $audit->setIsHttps(
            $data['is_https'] ?? false
        );

        $audit->setHasRobotsTxt(
            $data['has_robots_txt'] ?? false
        );

        $audit->setHasSitemapXml(
            $data['has_sitemap_xml'] ?? false
        );

        $audit->setIsMobileFriendly(
            $data['is_mobile_friendly'] ?? false
        );


        /*
         * ==========================================
         * Performance
         * ==========================================
         */

        $audit->setPageLoadTimeMs(
            $data['page_load_time_ms'] ?? null
        );

        $audit->setPagespeedDesktopScore(
            $data['pagespeed_desktop_score'] ?? null
        );

        $audit->setPagespeedMobileScore(
            $data['pagespeed_mobile_score'] ?? null
        );


        /*
         * ==========================================
         * Lighthouse scores
         * ==========================================
         */

        $audit->setAccessibilityScore(
            $data['accessibility_score'] ?? null
        );

        $audit->setBestPracticesScore(
            $data['best_practices_score'] ?? null
        );

        $audit->setSeoScore(
            $data['seo_score'] ?? null
        );


        /*
         * ==========================================
         * Metrics
         * ==========================================
         */

        $audit->setMetrics(
            $data['metrics'] ?? []
        );


        /*
         * ==========================================
         * Error message
         * ==========================================
         */

        $audit->setErrorMessage(
            $data['error_message'] ?? null
        );


        /*
         * ==========================================
         * 9. Recommendations
         * ==========================================
         */

        foreach (
            $data['recommendations'] ?? []
            as $item
        ) {

            $recommendation =
                new Recommendation();

            $recommendation->setAudit(
                $audit
            );

            $recommendation->setRecommendation(
                $item
            );

            $recommendation->setCreatedAt(
                new \DateTimeImmutable()
            );

            $em->persist(
                $recommendation
            );
        }


        /*
         * ==========================================
         * 10. Save everything
         * ==========================================
         */

        $em->flush();


        /*
         * ==========================================
         * 11. Response Frontend
         * ==========================================
         */

       return $this->json([
                'message' => 'Audit completed successfully',

                'audit_id' => $audit->getId(),

                'status' => $audit->getStatus(),

                'url' => $site->getUrl(),

                'global_score' => $audit->getGlobalScore(),

                'score_color' => $audit->getScoreColor(),

                'page_load_time_ms' => $audit->getPageLoadTimeMs(),

                'pagespeed_desktop_score' =>
                    $audit->getPagespeedDesktopScore(),

                'pagespeed_mobile_score' =>
                    $audit->getPagespeedMobileScore(),

                'accessibility_score' =>
                    $audit->getAccessibilityScore(),

                'best_practices_score' =>
                    $audit->getBestPracticesScore(),

                'seo_score' =>
                    $audit->getSeoScore(),

                'metrics' =>
                    $audit->getMetrics(),

                'https' =>
                    $audit->isHttps(),

                'robots_txt' =>
                    $audit->hasRobotsTxt(),

                'sitemap_xml' =>
                    $audit->hasSitemapXml(),

                'mobile_friendly' =>
                    $audit->isMobileFriendly(),

                'created_at' =>
                    $audit->getCreatedAt()
                        ?->format(\DateTimeInterface::ATOM),

            ], Response::HTTP_OK);
    }
}

