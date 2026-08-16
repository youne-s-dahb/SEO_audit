import { useEffect, useState, useCallback } from "react";
import {
    fetchAuditPages,
    fetchPageHeadings,
    fetchPageImages,
    fetchKeywordDensities,
    fetchAuditReports,
} from "../services/auditService";

/* =========================================================
   HELPERS
========================================================= */

// "/api/audit_pages/12" -> 12 | 12 -> 12 | {id:12} -> 12
function extractId(relation) {
    if (relation === null || relation === undefined) return null;

    if (typeof relation === "number") return relation;

    if (typeof relation === "string") {
        const match = relation.match(/(\d+)(?:\/?$)/);
        return match ? Number(match[1]) : null;
    }

    if (typeof relation === "object") {
        if (relation.id !== undefined) return Number(relation.id);
    }

    return null;
}

// Transforme un tableau d'IRIs/objets/ids en tableau d'ids numériques propres
function extractRelationIds(relationArray) {
    if (!Array.isArray(relationArray)) return [];
    return relationArray.map(extractId).filter((id) => id !== null);
}

function extractItems(payload) {
    if (!payload) return [];
    if (Array.isArray(payload)) return payload;
    if (Array.isArray(payload["hydra:member"])) return payload["hydra:member"];
    if (Array.isArray(payload.member)) return payload.member;
    if (Array.isArray(payload.data)) return payload.data;
    return [];
}

function belongsToAudit(item, auditId, relationKey = "audit") {
    const relatedId = extractId(item[relationKey]);
    return relatedId === null || relatedId === Number(auditId);
}

/* =========================================================
   NORMALIZERS
========================================================= */

function normalizeHeading(h) {
    return {
        id: h.id,
        level: h.headingLevel ?? h.heading_level ?? "",
        content: h.content ?? "",
        position: h.position ?? null,
    };
}

function normalizeImage(img) {
    return {
        id: img.id,
        url: img.imageUrl ?? img.image_url ?? "",
        hasAlt: img.hasAlt ?? img.has_alt ?? false,
        altText: img.altText ?? img.alt_text ?? "",
        fileSizeKb: img.fileSizeKb ?? img.file_size_kb ?? null,
        imageType: img.imageType ?? img.image_type ?? "",
    };
}

function normalizeKeyword(k) {
    return {
        id: k.id,
        keyword: k.keyword ?? "",
        occurrences: k.occurrences ?? 0,
        densityPercent: k.densityPercent ?? k.density_percent ?? 0,
    };
}

function normalizeReport(r) {
    return {
        id: r.id,
        format: r.format ?? "",
        filePath: r.filePath ?? r.file_path ?? "",
        generatedAt: r.generatedAt ?? r.generated_at ?? null,
    };
}

function normalizePage(p) {
    return {
        id: p.id,

        url: p.url ?? "",

        statusCode:
            p.statusCode ??
            p.status_code ??
            null,

        title:
            p.title ??
            "",

        titleLength:
            p.titleLength ??
            p.title_length ??
            null,

        metaDescription:
            p.metaDescription ??
            p.meta_description ??
            "",

        metaLength:
            p.metaLength ??
            p.meta_length ??
            null,

        canonicalUrl:
            p.canonicalUrl ??
            p.canonical_url ??
            "",

        metaRobots:
            p.metaRobots ??
            p.meta_robots ??
            "",

        langAttribute:
            p.langAttribute ??
            p.lang_attribute ??
            "",

        h1Count:
            p.h1Count ??
            p.h1_count ??
            0,

        h1IsUnique:
            p.h1IsUnique ??
            p.h1_is_unique ??
            null,

        wordCount:
            p.wordCount ??
            p.word_count ??
            0,

        internalLinksCount:
            p.internalLinksCount ??
            p.internal_links_count ??
            0,

        externalLinksCount:
            p.externalLinksCount ??
            p.external_links_count ??
            0,

        brokenLinksCount:
            p.brokenLinksCount ??
            p.broken_links_count ??
            0,

        imagesCount:
            p.imagesCount ??
            p.images_count ??
            0,

        imagesWithoutAltCount:
            p.imagesWithoutAltCount ??
            p.images_without_alt_count ??
            0,

        hasStructuredData:
            p.structuredData ??
            p.hasStructuredData ??
            p.has_structured_data ??
            false,

        isHttps:
            p.https ??
            p.isHttps ??
            p.is_https ??
            null,

        viewportMeta:
            p.viewportMeta ??
            p.viewport_meta ??
            null,

        responseTimeMs:
            p.responseTimeMs ??
            p.response_time_ms ??
            null,

        loadTimeMs:
            p.loadTimeMs ??
            p.load_time_ms ??
            null,

        crawlDepth:
            p.crawlDepth ??
            p.crawl_depth ??
            0,

        createdAt:
            p.createdAt ??
            p.created_at ??
            null,

        headingIds: extractRelationIds(
            p.heading ??
            p.headings ??
            []
        ),

        imageIds: extractRelationIds(
            p.image ??
            p.images ??
            []
        ),

        keywordIds: extractRelationIds(
            p.keywordDensities ??
            p.keyword_densities ??
            []
        ),

        headings: [],
        images: [],
        keywordDensities: [],
    };
}

/* =========================================================
   HOOK PRINCIPAL

   ⚠️ Le crawl détaillé (audit_pages, headings, images...) peut
   se terminer APRÈS le score global renvoyé par "Audit completed
   successfully". On retente donc automatiquement plusieurs fois
   avant d'afficher un état vide définitif.
========================================================= */

const MAX_RETRIES = 6;
const RETRY_DELAY_MS = 4000;

export function useAuditPagesData(auditId) {
    const [pages, setPages] = useState([]);
    const [reports, setReports] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState("");
    const [retryCount, setRetryCount] = useState(0);
    const [isRetrying, setIsRetrying] = useState(false);

    const fetchOnce = useCallback(async () => {
        console.log("1. AVANT Promise.all pages+reports"); // 👈 TEMPORAIRE

        // 1) Pages ET reports de cet audit, en parallele (gain de temps)
        const [pagesRes, reportsRes] = await Promise.all([
            fetchAuditPages({ audit: auditId }),
            fetchAuditReports({ audit: auditId }),
        ]);

        console.log("2. APRÈS Promise.all pages+reports", pagesRes, reportsRes); // 👈 TEMPORAIRE

        const rawPages = extractItems(pagesRes?.data ?? pagesRes).filter(
            (p) => belongsToAudit(p, auditId, "audit")
        );

        console.log("3. rawPages filtrées:", rawPages); // 👈 TEMPORAIRE

        const normalizedPages = rawPages.map(normalizePage);

        const rawReports = extractItems(reportsRes?.data ?? reportsRes).filter(
            (r) => belongsToAudit(r, auditId, "audit")
        );

        setReports(rawReports.map(normalizeReport));

        if (normalizedPages.length === 0) {
            console.log("4. STOP: normalizedPages est vide"); // 👈 TEMPORAIRE
            return [];
        }

        console.log("5. On continue vers headings/images/keywords"); // 👈 TEMPORAIRE

        // 3) Collections complètes (une seule fois), puis on associe
        //    à chaque page via les headingIds/imageIds/keywordIds
        //    qu'elle contient déjà.
        const [headingsRes, imagesRes, keywordsRes] = await Promise.all([
            fetchPageHeadings({}),
            fetchPageImages({}),
            fetchKeywordDensities({}),
        ]);

        const allHeadings = extractItems(headingsRes?.data ?? headingsRes).map(
            normalizeHeading
        );
        const allImages = extractItems(imagesRes?.data ?? imagesRes).map(
            normalizeImage
        );
        const allKeywords = extractItems(keywordsRes?.data ?? keywordsRes).map(
            normalizeKeyword
        );

        const headingsById = new Map(allHeadings.map((h) => [h.id, h]));
        const imagesById = new Map(allImages.map((i) => [i.id, i]));
        const keywordsById = new Map(allKeywords.map((k) => [k.id, k]));

        return normalizedPages.map((page) => ({
            ...page,

            headings: page.headingIds
                .map((id) => headingsById.get(id))
                .filter(Boolean)
                .sort((a, b) => (a.position ?? 0) - (b.position ?? 0)),

            images: page.imageIds.map((id) => imagesById.get(id)).filter(Boolean),

            keywordDensities: page.keywordIds
                .map((id) => keywordsById.get(id))
                .filter(Boolean)
                .sort((a, b) => b.densityPercent - a.densityPercent),
        }));
    }, [auditId]);

    const load = useCallback(
        async (attempt = 0) => {
            if (!auditId) {
                setPages([]);
                setReports([]);
                setIsLoading(false);
                return;
            }

            if (attempt === 0) {
                setIsLoading(true);
                setError("");
                setRetryCount(0);
            }

            try {
                const enrichedPages = await fetchOnce();

                if (enrichedPages.length === 0 && attempt < MAX_RETRIES) {
                    // Le crawl backend n'a probablement pas fini : on retente.
                    setIsRetrying(true);
                    setRetryCount(attempt + 1);

                    setTimeout(() => {
                        load(attempt + 1);
                    }, RETRY_DELAY_MS);

                    return;
                }

                setIsRetrying(false);
                setPages(enrichedPages);
                setIsLoading(false);
            } catch (err) {
                console.error("useAuditPagesData error:", err);
                setError("Impossible de charger les détails des pages de l'audit.");
                setIsRetrying(false);
                setIsLoading(false);
            }
        },
        [auditId, fetchOnce]
    );

    useEffect(() => {
        load(0);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [auditId]);

    return {
        pages,
        reports,
        isLoading,
        isRetrying,
        retryCount,
        maxRetries: MAX_RETRIES,
        error,
        reload: () => load(0),
    };
}