<?php

namespace App\Controller;

use App\Entity\Audit;
use App\Entity\AuditKeywordDensity;
use App\Entity\AuditPage;
use App\Entity\AuditPageHeading;
use App\Entity\AuditPageImage;
use App\Entity\AuditReport;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AuditPagesController extends AbstractController
{
    #[Route(
        '/api/audits/{id}/pages',
        name: 'audit_pages',
        methods: ['GET'],
        requirements: ['id' => '\d+']
    )]
    public function __invoke(
        int $id,
        EntityManagerInterface $em,
        #[CurrentUser] ?User $user
    ): JsonResponse {

        if (!$user) {
            return $this->json(['message' => 'Machi connecté.'], 401);
        }

        // -----------------------------------------------------
        // AUDIT + SECURITY
        // -----------------------------------------------------

        $audit = $em->getRepository(Audit::class)->find($id);

        if (!$audit) {
            return $this->json(['message' => 'Audit machi mawjoud.'], 404);
        }

        if ($audit->getRequestedBy()?->getId() !== $user->getId()) {
            return $this->json(['message' => "Ma3ndekch l'access l had audit."], 403);
        }

        // -----------------------------------------------------
        // PAGES
        // -----------------------------------------------------

        $pages = $em->getRepository(AuditPage::class)->findBy(
            ['audit' => $audit],
            ['id' => 'ASC']
        );

        if (empty($pages)) {
            return $this->json([
                'pages' => [],
                'reports' => $this->serializeReports($audit, $em),
            ]);
        }

        // -----------------------------------------------------
        // HEADINGS / IMAGES / KEYWORDS (bulk fetch, groupés par page)
        // -----------------------------------------------------

        $headings = $em->getRepository(AuditPageHeading::class)
            ->createQueryBuilder('h')
            ->where('h.auditPage IN (:pages)')
            ->setParameter('pages', $pages)
            ->orderBy('h.position', 'ASC')
            ->getQuery()
            ->getResult();

        $images = $em->getRepository(AuditPageImage::class)
            ->createQueryBuilder('i')
            ->where('i.auditPage IN (:pages)')
            ->setParameter('pages', $pages)
            ->getQuery()
            ->getResult();

        $keywords = $em->getRepository(AuditKeywordDensity::class)
            ->createQueryBuilder('k')
            ->where('k.auditPage IN (:pages)')
            ->setParameter('pages', $pages)
            ->getQuery()
            ->getResult();

        $headingsByPage = [];
        foreach ($headings as $h) {
            $headingsByPage[$h->getAuditPage()->getId()][] = [
                'id' => $h->getId(),
                'level' => $h->getHeadingLevel(),
                'content' => $h->getContent(),
                'position' => $h->getPosition(),
            ];
        }

        $imagesByPage = [];
        foreach ($images as $img) {
            $imagesByPage[$img->getAuditPage()->getId()][] = [
                'id' => $img->getId(),
                'url' => $img->getImageUrl(),
                'hasAlt' => $img->isHasAlt(),
                'altText' => $img->getAltText(),
                'imageType' => $img->getImageType(),
                'fileSizeKb' => $img->getFileSizeKb(),
            ];
        }

        $keywordsByPage = [];
        foreach ($keywords as $k) {
            $keywordsByPage[$k->getAuditPage()->getId()][] = [
                'id' => $k->getId(),
                'keyword' => $k->getKeyword(),
                'occurrences' => $k->getOccurrences(),
                'densityPercent' => $k->getDensityPercent(),
            ];
        }

        // -----------------------------------------------------
        // BUILD RESPONSE
        // -----------------------------------------------------

        $pagesData = array_map(
            function (AuditPage $page) use ($headingsByPage, $imagesByPage, $keywordsByPage) {

                $pageId = $page->getId();

                return [
                    'id' => $pageId,
                    'url' => $page->getUrl(),
                    'statusCode' => $page->getStatusCode(),
                    'title' => $page->getTitle(),
                    'titleLength' => $page->getTitleLength(),
                    'metaDescription' => $page->getMetaDescription(),
                    'metaLength' => $page->getMetaLength(),
                    'canonicalUrl' => $page->getCanonicalUrl(),
                    'metaRobots' => $page->getMetaRobots(),
                    'langAttribute' => $page->getLangAttribute(),
                    'h1Count' => $page->getH1Count(),
                    'h1IsUnique' => $page->isH1IsUnique(),
                    'wordCount' => $page->getWordCount(),
                    'internalLinksCount' => $page->getInternalLinksCount(),
                    'externalLinksCount' => $page->getExternalLinksCount(),
                    'brokenLinksCount' => $page->getBrokenLinksCount(),
                    'imagesCount' => $page->getImagesCount(),
                    'imagesWithoutAltCount' => $page->getImagesWithoutAltCount(),
                    'hasStructuredData' => $page->isHasStructuredData(),
                    'isHttps' => $page->isIsHttps(),
                    'viewportMeta' => $page->getViewportMeta(),
                    'responseTimeMs' => $page->getResponseTimeMs(),
                    'loadTimeMs' => $page->getLoadTimeMs(),
                    'crawlDepth' => $page->getCrawlDepth(),
                    'createdAt' => $page->getCreatedAt()?->format(DATE_ATOM),
                    'headings' => $headingsByPage[$pageId] ?? [],
                    'images' => $imagesByPage[$pageId] ?? [],
                    'keywordDensities' => $keywordsByPage[$pageId] ?? [],
                ];
            },
            $pages
        );

        return $this->json([
            'pages' => $pagesData,
            'reports' => $this->serializeReports($audit, $em),
        ]);
    }

    private function serializeReports(Audit $audit, EntityManagerInterface $em): array
    {
        $reports = $em->getRepository(AuditReport::class)->findBy(
            ['audit' => $audit],
            ['generatedAt' => 'DESC']
        );

        return array_map(
            static fn(AuditReport $r) => [
                'id' => $r->getId(),
                'format' => $r->getFormat(),
                'filePath' => "/api/audit-report/{$r->getId()}/download",
                'generatedAt' => $r->getGeneratedAt()?->format(DATE_ATOM),
            ],
            $reports
        );
    }
}