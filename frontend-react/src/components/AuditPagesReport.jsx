import { useState } from "react";
import { useAuditPagesData } from "../hooks/useAuditPagesData"; // ⚠️ ADAPTE le chemin si besoin

/**
 * AuditPagesReport
 * Composant autonome — pages, headings, images, keyword density, reports.
 * Utilisable à la fois dans Report.jsx (variant="report") et
 * AuditResult.jsx (variant="home").
 *
 * Props:
 *  - auditId (number|string) requis
 *  - variant "report" | "home" -> change le préfixe de classes CSS
 */
export default function AuditPagesReport({ auditId, variant = "report" }) {
    console.log("AuditPagesReport a reçu auditId:", auditId, typeof auditId); // 👈 TEMPORAIRE

    const {
        pages,
        reports,
        isLoading,
        isRetrying,
        retryCount,
        maxRetries,
        error,
        reload,
    } = useAuditPagesData(auditId);

    const prefix = variant === "home" ? "apr-home" : "apr-report";

    if (isLoading) {
        return (
            <div className={`${prefix}-loading`}>
                <div className="apr-spinner" />
                <p>
                    {isRetrying
                        ? `Analyse des pages en cours côté serveur, nouvelle tentative (${retryCount}/${maxRetries})...`
                        : "Chargement des pages analysées..."}
                </p>
            </div>
        );
    }

    if (error) {
        return (
            <div className={`${prefix}-error`}>
                <p>{error}</p>
                <button type="button" onClick={reload}>
                    Réessayer
                </button>
            </div>
        );
    }

    if (!pages || pages.length === 0) {
        return (
            <div className={`${prefix}-empty`}>
                <p>
                    Aucune page détaillée n'est encore disponible pour cet audit.
                    L'analyse détaillée peut prendre quelques instants de plus après
                    le score global.
                </p>
                <button type="button" onClick={reload}>
                    Réessayer
                </button>
            </div>
        );
    }

    return (
        <div className={`${prefix}-wrapper`}>

            <div className={`${prefix}-heading`}>
                
            </div>

            <div className={`${prefix}-pages`}>
                {pages.map((page) => (
                    <PageBlock key={page.id} page={page} prefix={prefix} />
                ))}
            </div>

            {reports.length > 0 && (
                <ReportsBlock reports={reports} prefix={prefix} />
            )}

        </div>
    );
}

/* =========================================================
   PAGE BLOCK
========================================================= */

function PageBlock({ page, prefix }) {
    const [open, setOpen] = useState(true);

    return (
        <div className={`${prefix}-page`}>

            <button
                type="button"
                className={`${prefix}-page-header`}
                onClick={() => setOpen((v) => !v)}
            >
                <div>
                    <span className={`${prefix}-page-status ${statusTone(page.statusCode)}`}>
                        {page.statusCode ?? "—"}
                    </span>
                    <strong>{page.title || page.url}</strong>
                    <span className={`${prefix}-page-url`}>{page.url}</span>
                </div>
                <span className={`${prefix}-chevron ${open ? "open" : ""}`}>▾</span>
            </button>

            {open && (
                <div className={`${prefix}-page-body`}>

                    
                    {/* =====================================================
                        INFORMATIONS DE LA PAGE
                    ===================================================== */}

                    <div className={`${prefix}-mini-grid`}>

                        <Mini
                            label="Titre"
                            value={page.title || "—"}
                            sub={
                                page.titleLength != null
                                    ? `${page.titleLength} caractères`
                                    : undefined
                            }
                        />

                        <Mini
                            label="Meta description"
                            value={page.metaDescription || "—"}
                            sub={
                                page.metaLength != null
                                    ? `${page.metaLength} caractères`
                                    : undefined
                            }
                        />

                        <Mini
                            label="Canonical"
                            value={page.canonicalUrl || "—"}
                        />

                        <Mini
                            label="Meta robots"
                            value={page.metaRobots || "—"}
                        />

                        <Mini
                            label="Langue"
                            value={page.langAttribute || "—"}
                        />

                        <Mini
                            label="Nombre de mots"
                            value={page.wordCount ?? 0}
                        />

                        <Mini
                            label="Liens internes"
                            value={page.internalLinksCount ?? 0}
                        />

                        <Mini
                            label="Liens externes"
                            value={page.externalLinksCount ?? 0}
                        />

                        <Mini
                            label="Liens cassés"
                            value={page.brokenLinksCount ?? 0}
                            warn={Number(page.brokenLinksCount) > 0}
                        />

                        <Mini
                            label="Nombre de H1"
                            value={page.h1Count ?? 0}
                            sub={
                                page.h1IsUnique === true
                                    ? "unique"
                                    : page.h1IsUnique === false
                                        ? "non unique"
                                        : "non vérifié"
                            }
                            warn={page.h1IsUnique === false}
                        />

                        <Mini
                            label="Images"
                            value={page.imagesCount ?? 0}
                        />

                        <Mini
                            label="Images sans alt"
                            value={page.imagesWithoutAltCount ?? 0}
                            warn={Number(page.imagesWithoutAltCount) > 0}
                        />

                        <Mini
                            label="Données structurées"
                            value={
                                page.hasStructuredData
                                    ? "✓ Présentes"
                                    : "✕ Absentes"
                            }
                            warn={!page.hasStructuredData}
                        />

                        <Mini
                            label="HTTPS"
                            value={
                                page.isHttps === true
                                    ? "✓ Activé"
                                    : page.isHttps === false
                                        ? "✕ Non sécurisé"
                                        : "—"
                            }
                            warn={page.isHttps === false}
                        />

                        <Mini
                            label="Viewport"
                            value={
                                page.viewportMeta
                                    ? "✓ Présent"
                                    : "✕ Absent"
                            }
                            warn={!page.viewportMeta}
                        />

                        <Mini
                            label="Temps de réponse"
                            value={
                                page.responseTimeMs != null
                                    ? `${page.responseTimeMs} ms`
                                    : "—"
                            }
                        />

                        <Mini
                            label="Temps de chargement"
                            value={
                                page.loadTimeMs != null
                                    ? `${page.loadTimeMs} ms`
                                    : "—"
                            }
                        />

                        <Mini
                            label="Profondeur de crawl"
                            value={page.crawlDepth ?? 0}
                        />

                        <Mini
                            label="Date de création"
                            value={
                                page.createdAt
                                    ? new Date(page.createdAt).toLocaleString("fr-FR")
                                    : "—"
                            }
                        />

                    </div>

                    {/* HEADINGS */}
                    {page.headings.length > 0 && (
                        <div className={`${prefix}-subsection`}>
                            <h4>Structure des titres ({page.headings.length})</h4>
                            <div className={`${prefix}-headings-list`}>
                                {page.headings.map((h) => (
                                    <div
                                        key={h.id}
                                        className={`${prefix}-heading-item level-${h.level?.toLowerCase()}`}
                                    >
                                        <span className={`${prefix}-heading-tag`}>{h.level}</span>
                                        <p>{h.content}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* IMAGES */}
                    {page.images.length > 0 && (
                        <div className={`${prefix}-subsection`}>
                            <h4>
                                Images ({page.images.length}) —{" "}
                                {page.images.filter((i) => !i.hasAlt).length} sans attribut alt
                            </h4>
                            <div className={`${prefix}-images-grid`}>
                                {page.images.map((img) => (
                                    <div key={img.id} className={`${prefix}-image-card`}>
                                        <div className={`${prefix}-image-alt-badge ${img.hasAlt ? "ok" : "bad"}`}>
                                            {img.hasAlt ? "✓ alt" : "✕ pas d'alt"}
                                        </div>
                                        <p className={`${prefix}-image-url`} title={img.url}>{img.url}</p>
                                        {img.altText && <p className={`${prefix}-image-alt-text`}>{img.altText}</p>}
                                        <div className={`${prefix}-image-meta`}>
                                            <span>{img.imageType || "—"}</span>
                                            <span>{img.fileSizeKb != null ? `${img.fileSizeKb} Ko` : "—"}</span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* KEYWORD DENSITY */}
                    {page.keywordDensities.length > 0 && (
                        <div className={`${prefix}-subsection`}>
                            <h4>Densité des mots-clés</h4>
                            <div className={`${prefix}-keywords-list`}>
                                {page.keywordDensities.map((k) => (
                                    <div key={k.id} className={`${prefix}-keyword-row`}>
                                        <span className={`${prefix}-keyword-name`}>{k.keyword}</span>
                                        <div className={`${prefix}-keyword-bar-track`}>
                                            <div
                                                className={`${prefix}-keyword-bar-fill`}
                                                style={{ width: `${Math.min(k.densityPercent * 10, 100)}%` }}
                                            />
                                        </div>
                                        <span className={`${prefix}-keyword-value`}>
                                            {k.densityPercent}% · {k.occurrences} occ.
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                </div>
            )}

        </div>
    );
}

function Mini({ label, value, sub, warn }) {
    return (
        <div className={`mini-stat ${warn ? "warn" : ""}`}>
            <span>{label}</span>
            <strong>{value}</strong>
            {sub && <em>{sub}</em>}
        </div>
    );
}

function statusTone(code) {
    if (!code) return "unknown";
    if (code >= 200 && code < 300) return "ok";
    if (code >= 300 && code < 400) return "redirect";
    return "bad";
}

/* =========================================================
   REPORTS BLOCK
========================================================= */

function ReportsBlock({ reports, prefix }) {
    return (
        <div className={`${prefix}-reports`}>
            <h3>Rapports générés</h3>
            <div className={`${prefix}-reports-list`}>
                {reports.map((r) => (
                    <div key={r.id} className={`${prefix}-report-item`}>
                        <span className={`${prefix}-report-format`}>{r.format?.toUpperCase()}</span>
                        <a href={r.filePath} target="_blank" rel="noopener noreferrer">
                            Ouvrir le fichier
                        </a>
                        <span className={`${prefix}-report-date`}>
                            {r.generatedAt ? new Date(r.generatedAt).toLocaleString("fr-FR") : "—"}
                        </span>
                    </div>
                ))}
            </div>
        </div>
    );
}
