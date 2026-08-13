import { useEffect, useMemo, useState } from "react";
import { Link, useParams } from "react-router-dom";
import html2pdf from "html2pdf.js";
import { useAuth } from "../components/AuthContext";

function normalizeAuditData(rawAudit) {
    if (!rawAudit) {
        return null;
    }

    const site = rawAudit.site || {};
    const googleMap = rawAudit.googleMap || rawAudit.google_map || rawAudit.googleMaps || rawAudit.google_maps || null;
    const technicalSeo = rawAudit.technicalSeo || rawAudit.technical_seo || {};
    const metrics = rawAudit.metrics || {};
    const score = rawAudit.score ?? rawAudit.globalScore ?? rawAudit.global_score ?? null;
    const reportUrl = rawAudit.url || site.url || "";

    return {
        ...rawAudit,
        id: rawAudit.id ?? rawAudit.audit_id ?? null,
        url: reportUrl,
        siteName: resolveSiteName(rawAudit.siteName || rawAudit.site_name || site.name || "", reportUrl),
        auditType: rawAudit.auditType || rawAudit.audit_type || (googleMap ? "google_maps" : "seo"),
        score,
        globalScore: score,
        global_score: score,
        desktopScore: rawAudit.desktopScore ?? rawAudit.pagespeedDesktopScore ?? rawAudit.pagespeed_desktop_score ?? null,
        pagespeed_desktop_score: rawAudit.pagespeedDesktopScore ?? rawAudit.pagespeed_desktop_score ?? rawAudit.desktopScore ?? null,
        mobileScore: rawAudit.mobileScore ?? rawAudit.pagespeedMobileScore ?? rawAudit.pagespeed_mobile_score ?? null,
        pagespeed_mobile_score: rawAudit.pagespeedMobileScore ?? rawAudit.pagespeed_mobile_score ?? rawAudit.mobileScore ?? null,
        seoScore: rawAudit.seoScore ?? rawAudit.seo_score ?? null,
        seo_score: rawAudit.seoScore ?? rawAudit.seo_score ?? null,
        accessibilityScore: rawAudit.accessibilityScore ?? rawAudit.accessibility_score ?? null,
        accessibility_score: rawAudit.accessibilityScore ?? rawAudit.accessibility_score ?? null,
        bestPracticesScore: rawAudit.bestPracticesScore ?? rawAudit.best_practices_score ?? null,
        best_practices_score: rawAudit.bestPracticesScore ?? rawAudit.best_practices_score ?? null,
        pageLoadTimeMs: rawAudit.pageLoadTimeMs ?? rawAudit.page_load_time_ms ?? technicalSeo.pageLoadTimeMs ?? technicalSeo.page_load_time_ms ?? null,
        page_load_time_ms: rawAudit.page_load_time_ms ?? rawAudit.pageLoadTimeMs ?? technicalSeo.page_load_time_ms ?? technicalSeo.pageLoadTimeMs ?? null,
        https: rawAudit.https ?? technicalSeo.https ?? null,
        robots_txt: rawAudit.robotsTxt ?? rawAudit.robots_txt ?? technicalSeo.robotsTxt ?? technicalSeo.robots_txt ?? null,
        sitemap_xml: rawAudit.sitemapXml ?? rawAudit.sitemap_xml ?? technicalSeo.sitemapXml ?? technicalSeo.sitemap_xml ?? null,
        mobile_friendly: rawAudit.mobileFriendly ?? rawAudit.mobile_friendly ?? technicalSeo.mobileFriendly ?? technicalSeo.mobile_friendly ?? null,
        metrics: normalizeMetrics(metrics),
        technicalSeo,
        googleMap,
        googleMapsUrl: rawAudit.googleMapsUrl || rawAudit.google_maps_url || googleMap?.googleMapsUrl || googleMap?.google_maps_url || null,
        recommendations: normalizeRecommendations(rawAudit.recommendations),
        createdAt: rawAudit.createdAt || rawAudit.created_at || null,
        scoreColor: rawAudit.scoreColor || rawAudit.score_color || null,
        status: rawAudit.status || null,
        errorMessage: rawAudit.errorMessage || rawAudit.error_message || null,
    };
}

function normalizeMetrics(metrics) {
    const normalized = metrics || {};

    return {
        ...normalized,
        first_contentful_paint: normalized.first_contentful_paint ?? normalized.firstContentfulPaint ?? null,
        largest_contentful_paint: normalized.largest_contentful_paint ?? normalized.largestContentfulPaint ?? null,
        speed_index: normalized.speed_index ?? normalized.speedIndex ?? null,
        total_blocking_time: normalized.total_blocking_time ?? normalized.totalBlockingTime ?? null,
        cumulative_layout_shift: normalized.cumulative_layout_shift ?? normalized.cumulativeLayoutShift ?? null,
        time_to_interactive: normalized.time_to_interactive ?? normalized.timeToInteractive ?? null,
    };
}

function normalizeRecommendations(recommendations) {
    if (!Array.isArray(recommendations)) {
        return [];
    }

    return recommendations
        .map((item) => {
            if (item === null || item === undefined) {
                return "";
            }

            if (typeof item === "string") {
                return item.trim();
            }

            if (typeof item === "object") {
                return String(
                    item.recommendation ||
                    item.title ||
                    item.name ||
                    item.description ||
                    item.label ||
                    ""
                ).trim();
            }

            return String(item).trim();
        })
        .filter(Boolean);
}

function resolveSiteName(siteName, url) {
    const candidate = String(siteName || "").trim();
    if (candidate && !/^(site|untitled site)$/i.test(candidate)) {
        return candidate;
    }

    if (url) {
        try {
            return new URL(url).hostname.replace(/^www\./i, "");
        } catch {
            return String(url)
                .replace(/^https?:\/\//i, "")
                .replace(/^www\./i, "")
                .split("/")[0] || "Site";
        }
    }

    return "Site";
}

function getScoreClass(score) {
    if (score >= 90) {
        return "score-excellent";
    }

    if (score >= 70) {
        return "score-good";
    }

    if (score >= 50) {
        return "score-mid";
    }

    return "score-bad";
}

function getScoreLabel(score) {
    if (score >= 90) {
        return "Excellent";
    }

    if (score >= 70) {
        return "Bon";
    }

    if (score >= 50) {
        return "À améliorer";
    }

    return "Critique";
}

function formatScore(value) {
    if (value === null || value === undefined || value === "") {
        return "—";
    }

    const numeric = Number(value);
    return Number.isFinite(numeric) ? Math.round(numeric) : "—";
}

function formatDate(value) {
    if (!value) {
        return "Date inconnue";
    }

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
        return "Date inconnue";
    }

    return parsed.toLocaleString("fr-FR", {
        day: "2-digit",
        month: "long",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

function statusToLabel(value) {
    if (value === true || value === 1 || value === "true") {
        return "✓ OK";
    }

    if (value === false || value === 0 || value === "false") {
        return "✕ Problème";
    }

    return "! À vérifier";
}

function statusToTone(value) {
    if (value === true || value === 1 || value === "true") {
        return "ok";
    }

    if (value === false || value === 0 || value === "false") {
        return "bad";
    }

    return "warn";
}

function recommendationPriority(score, hasGoogleMaps) {
    if (hasGoogleMaps) {
        return 0;
    }

    if (score === null || score === undefined) {
        return 1;
    }

    return 2;
}

export default function Report() {
    const { id } = useParams();
    const { getAuditDetail } = useAuth();
    const [audit, setAudit] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState("");

    useEffect(() => {
        loadAudit();
    }, [id]);

    async function loadAudit() {
        setIsLoading(true);
        setError("");

        try {
            const result = await getAuditDetail(id);

            if (!result.ok) {
                setError(result.error || "Ma9dertch njib had l'audit.");
                return;
            }

            setAudit(normalizeAuditData(result.audit));
        } catch (err) {
            console.error("REPORT ERROR:", err);
            setError("Erreur lors du chargement du rapport.");
        } finally {
            setIsLoading(false);
        }
    }

    async function downloadPDF() {
        if (!audit) {
            return;
        }

        const element = document.getElementById("seo-report");
        if (!element) {
            return;
        }

        const button = document.querySelector(".report-print-button");

        if (button) {
            button.disabled = true;
            button.innerText = "Création du PDF...";
        }

        try {
            const options = {
                margin: [8, 8, 8, 8],
                filename: `SEO-Audit-${audit.siteName || "report"}.pdf`,
                image: { type: "jpeg", quality: 0.98 },
                html2canvas: {
                    scale: 2,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: "#0f141c",
                },
                jsPDF: { unit: "mm", format: "a4", orientation: "portrait" },
                pagebreak: { mode: ["css", "legacy"] },
            };

            await html2pdf().set(options).from(element).save();
        } catch (err) {
            console.error("PDF ERROR:", err);
            alert("Ma9drnach nsaybo PDF.");
        } finally {
            if (button) {
                button.disabled = false;
                button.innerText = "↓ Télécharger PDF";
            }
        }
    }

    const score = Number(audit?.score ?? audit?.global_score ?? 0) || 0;
    const scoreClassName = getScoreClass(score);
    const scoreLabel = getScoreLabel(score);
    const siteName = audit?.siteName || resolveSiteName(audit?.site?.name, audit?.url);
    const reportUrl = audit?.url || audit?.site?.url || "";
    const googleMap = audit?.googleMap || null;
    const hasGoogleMapsData = Boolean(googleMap);
    const recommendations = audit?.recommendations || [];

    const strengths = useMemo(() => {
        const items = [];

        if (audit?.https === true) {
            items.push("HTTPS est actif");
        }

        if (audit?.robots_txt === true) {
            items.push("robots.txt est disponible");
        }

        if (audit?.sitemap_xml === true) {
            items.push("sitemap.xml est disponible");
        }

        if (audit?.mobile_friendly === true) {
            items.push("Le site est mobile friendly");
        }

        if (typeof audit?.seo_score === "number" && audit.seo_score >= 80) {
            items.push("Le score SEO est solide");
        }

        if (typeof audit?.pagespeed_mobile_score === "number" && audit.pagespeed_mobile_score >= 80) {
            items.push("La performance mobile est bonne");
        }

        if (hasGoogleMapsData) {
            items.push("Une présence Google Maps est détectée");
        }

        return items;
    }, [audit, hasGoogleMapsData]);

    const improvements = useMemo(() => {
        const items = [];

        if (audit?.https === false) {
            items.push("Activer HTTPS");
        }

        if (audit?.robots_txt === false) {
            items.push("Ajouter robots.txt");
        }

        if (audit?.sitemap_xml === false) {
            items.push("Ajouter sitemap.xml");
        }

        if (audit?.mobile_friendly === false) {
            items.push("Améliorer l'expérience mobile");
        }

        if (typeof audit?.pagespeed_desktop_score === "number" && audit.pagespeed_desktop_score < 70) {
            items.push("Optimiser la performance desktop");
        }

        if (typeof audit?.pagespeed_mobile_score === "number" && audit.pagespeed_mobile_score < 70) {
            items.push("Optimiser la performance mobile");
        }

        if (typeof audit?.seo_score === "number" && audit.seo_score < 70) {
            items.push("Renforcer les bases SEO");
        }

        return items;
    }, [audit]);

    const sortedRecommendations = useMemo(() => {
        return [...recommendations].sort((a, b) => recommendationPriority(a, hasGoogleMapsData) - recommendationPriority(b, hasGoogleMapsData));
    }, [recommendations, hasGoogleMapsData]);

    if (isLoading) {
        return (
            <div className="report-page">
                <div className="report-page-loading">
                    <div className="spinner" />
                    <h3>Chargement du rapport...</h3>
                    <p>Kaytchargé les détails dyal l'audit.</p>
                </div>
            </div>
        );
    }

    if (error || !audit) {
        return (
            <div className="report-page">
                <div className="report-page-loading">
                    <h3>Impossible de charger le rapport</h3>
                    <p>{error || "Audit machi mawjoud."}</p>
                    <Link to="/history" className="history-report-button" style={{ marginTop: "18px" }}>
                        ← Retour à l'historique
                    </Link>
                </div>
            </div>
        );
    }

    return (
        <div className="report-page">
            <div className="report-toolbar no-print">
                <Link to="/history" className="report-back">
                    ← Retour à l'historique
                </Link>
                <button type="button" className="report-print-button" onClick={downloadPDF}>
                    ↓ Télécharger PDF
                </button>
            </div>

            <div id="seo-report" className="professional-report">
                <section className="report-cover">
                    <div className="report-brand">
                        <div className="report-brand-mark">S</div>
                        <div>
                            <strong>{hasGoogleMapsData ? "Google Maps Audit" : "SEO Audit"}</strong>
                            <span>Professional Report</span>
                        </div>
                    </div>

                    <div className="report-cover-content">
                        <span className="report-label">RAPPORT D'AUDIT</span>
                        <h1>{siteName}</h1>
                        <p className="report-url">{reportUrl}</p>
                        <div className="report-meta-line">
                            <span>{audit.status === "failed" ? "Audit échoué" : "Audit terminé"}</span>
                            <span>•</span>
                            <span>{formatDate(audit.createdAt)}</span>
                        </div>
                    </div>

                    <div className="report-score-hero">
                        <div className={`report-score-circle ${scoreClassName}`}>
                            <strong>{formatScore(score)}</strong>
                            <span>/100</span>
                        </div>
                        <div>
                            <strong>{scoreLabel}</strong>
                            <span>{hasGoogleMapsData ? "Audit local Google Maps" : "Score global du site"}</span>
                        </div>
                    </div>
                </section>

                <section className="report-section">
                    <div className="report-section-heading">
                        <div>
                            <span>01</span>
                            <div>
                                <h2>{hasGoogleMapsData ? "Google Maps" : "Scores principaux"}</h2>
                                <p>{hasGoogleMapsData ? "Données locales réellement stockées" : "Vue globale des performances SEO"}</p>
                            </div>
                        </div>
                    </div>

                    {hasGoogleMapsData ? (
                        <div className="report-google-maps-card">
                            <div className="report-google-maps-hero">
                                <div>
                                    <span className="report-pill">Google Maps</span>
                                    <h3>{googleMap.businessName || googleMap.title || siteName}</h3>
                                    <p>{googleMap.address || "—"}</p>
                                </div>
                                <div className={`report-google-status ${statusToTone(googleMap.isPresent ?? googleMap.is_present ?? null)}`}>
                                    {statusToLabel(googleMap.isPresent ?? googleMap.is_present ?? null)}
                                </div>
                            </div>

                            <div className="report-score-grid">
                                <ReportScoreCard title="Présence" value={googleMap.isPresent ?? googleMap.is_present ?? null} booleanMode />
                                <ReportScoreCard title="Évaluation" value={googleMap.rating ?? null} max={5} />
                                <ReportScoreCard title="Avis" value={googleMap.reviewsCount ?? googleMap.reviews_count ?? null} />
                            </div>

                            <div className="report-metrics-grid">
                                <ReportMetric label="Business name" value={googleMap.businessName ?? googleMap.business_name ?? "—"} />
                                <ReportMetric label="Presence" value={statusToLabel(googleMap.isPresent ?? googleMap.is_present ?? null)} />
                                <ReportMetric label="Address" value={googleMap.address ?? "—"} />
                                <ReportMetric label="Rating" value={googleMap.rating ?? "—"} />
                                <ReportMetric label="Reviews count" value={googleMap.reviewsCount ?? googleMap.reviews_count ?? "—"} />
                                <ReportMetric label="Place ID" value={googleMap.placeId ?? googleMap.place_id ?? "—"} />
                                <ReportMetric label="Google Maps URL" value={audit.googleMapsUrl || googleMap.googleMapsUrl || googleMap.google_maps_url || "—"} />
                                <ReportMetric label="Status" value={googleMap.status || audit.auditType || "—"} />
                            </div>
                        </div>
                    ) : (
                        <div className="report-score-grid">
                            <ReportScoreCard title="Performance" subtitle="Desktop" value={audit.pagespeed_desktop_score} />
                            <ReportScoreCard title="Performance" subtitle="Mobile" value={audit.pagespeed_mobile_score} />
                            <ReportScoreCard title="SEO" value={audit.seo_score} />
                            <ReportScoreCard title="Accessibility" value={audit.accessibility_score} />
                            <ReportScoreCard title="Best Practices" value={audit.best_practices_score} />
                        </div>
                    )}
                </section>

                {!hasGoogleMapsData && (
                    <>
                        <section className="report-section">
                            <div className="report-section-heading">
                                <div>
                                    <span>02</span>
                                    <div>
                                        <h2>Performance</h2>
                                        <p>Métriques techniques de la page</p>
                                    </div>
                                </div>
                            </div>

                            <div className="report-metrics-grid">
                                <ReportMetric label="First Contentful Paint" value={audit.metrics?.first_contentful_paint ?? "—"} />
                                <ReportMetric label="Largest Contentful Paint" value={audit.metrics?.largest_contentful_paint ?? "—"} />
                                <ReportMetric label="Speed Index" value={audit.metrics?.speed_index ?? "—"} />
                                <ReportMetric label="Total Blocking Time" value={audit.metrics?.total_blocking_time ?? "—"} />
                                <ReportMetric label="Cumulative Layout Shift" value={audit.metrics?.cumulative_layout_shift ?? "—"} />
                                <ReportMetric label="Time To Interactive" value={audit.metrics?.time_to_interactive ?? "—"} />
                            </div>

                            <div className="report-load-time">
                                <div>
                                    <span>Temps de chargement</span>
                                    <strong>{audit.page_load_time_ms !== null && audit.page_load_time_ms !== undefined ? `${audit.page_load_time_ms} ms` : "—"}</strong>
                                </div>
                                <div className="report-load-icon">⚡</div>
                            </div>
                        </section>

                        <section className="report-section">
                            <div className="report-section-heading">
                                <div>
                                    <span>03</span>
                                    <div>
                                        <h2>Technical SEO</h2>
                                        <p>Vérifications techniques du site</p>
                                    </div>
                                </div>
                            </div>

                            <div className="technical-grid">
                                <TechnicalCheck label="HTTPS" value={audit.https} />
                                <TechnicalCheck label="Robots.txt" value={audit.robots_txt} />
                                <TechnicalCheck label="Sitemap.xml" value={audit.sitemap_xml} />
                                <TechnicalCheck label="Mobile Friendly" value={audit.mobile_friendly} />
                                <TechnicalCheck label="Loading time" value={audit.page_load_time_ms ?? null} numeric={true} />
                            </div>
                        </section>
                    </>
                )}

                <section className="report-section">
                    <div className="report-section-heading">
                        <div>
                            <span>{hasGoogleMapsData ? "02" : "04"}</span>
                            <div>
                                <h2>Points forts</h2>
                                <p>Basé uniquement sur les checks réellement positifs</p>
                            </div>
                        </div>
                    </div>

                    {strengths.length > 0 ? (
                        <div className="report-list report-list-good">
                            {strengths.map((item) => (
                                <div key={item} className="report-list-item good">
                                    <span>✓</span>
                                    <p>{item}</p>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="report-empty-state">Aucun point fort clairement identifié dans les données stockées.</div>
                    )}
                </section>

                <section className="report-section">
                    <div className="report-section-heading">
                        <div>
                            <span>{hasGoogleMapsData ? "03" : "05"}</span>
                            <div>
                                <h2>À améliorer</h2>
                                <p>Basé uniquement sur les checks réellement négatifs ou manquants</p>
                            </div>
                        </div>
                    </div>

                    {improvements.length > 0 ? (
                        <div className="report-list report-list-warn">
                            {improvements.map((item) => (
                                <div key={item} className="report-list-item warn">
                                    <span>!</span>
                                    <p>{item}</p>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="report-empty-state">Aucune faiblesse critique n’a été enregistrée pour cet audit.</div>
                    )}
                </section>

                {!hasGoogleMapsData && (
                    <section className="report-section">
                        <div className="report-section-heading">
                            <div>
                                <span>06</span>
                                <div>
                                    <h2>Priorités</h2>
                                    <p>Les recommandations les plus urgentes d’abord</p>
                                </div>
                            </div>
                        </div>

                        {sortedRecommendations.length > 0 ? (
                            <div className="report-recommendations">
                                {sortedRecommendations.map((recommendation, index) => (
                                    <div className="report-recommendation" key={`${recommendation}-${index}`}>
                                        <span className="recommendation-number">{String(index + 1).padStart(2, "0")}</span>
                                        <div>
                                            <strong>{index === 0 ? "Priorité élevée" : `Priorité ${index + 1}`}</strong>
                                            <p>{recommendation}</p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="report-empty-state">Aucune recommandation disponible dans les données stockées.</div>
                        )}
                    </section>
                )}

                <footer className="report-footer">
                    <div>
                        <strong>{hasGoogleMapsData ? "Google Maps Audit" : "SEO Audit"}</strong>
                        <span>Rapport généré automatiquement</span>
                    </div>
                    <div>
                        <span>{siteName}</span>
                        <span>{formatDate(audit.createdAt)}</span>
                    </div>
                </footer>
            </div>
        </div>
    );
}

function ReportScoreCard({ title, subtitle, value, max = 100, booleanMode = false }) {
    const numericValue = value === null || value === undefined ? null : Number(value);
    let className = "";

    if (booleanMode) {
        if (value === true || value === "true" || value === 1) {
            className = "score-good";
        } else if (value === false || value === "false" || value === 0) {
            className = "score-bad";
        } else {
            className = "score-mid";
        }
    } else if (numericValue !== null && !Number.isNaN(numericValue)) {
        if (numericValue >= max * 0.8) {
            className = "score-good";
        } else if (numericValue >= max * 0.5) {
            className = "score-mid";
        } else {
            className = "score-bad";
        }
    }

    return (
        <div className="report-score-card">
            <div>
                <strong>{title}</strong>
                {subtitle && <span>{subtitle}</span>}
            </div>
            <div>
                <strong className={className}>{booleanMode ? statusToLabel(value) : formatScore(numericValue)}</strong>
                {!booleanMode && numericValue !== null && !Number.isNaN(numericValue) && <span>/ {max}</span>}
            </div>
        </div>
    );
}

function ReportMetric({ label, value }) {
    return (
        <div className="report-metric">
            <span>{label}</span>
            <strong>{value ?? "—"}</strong>
        </div>
    );
}

function TechnicalCheck({ label, value, numeric = false }) {
    const isNumeric = numeric && value !== null && value !== undefined && value !== "";
    const status = numeric
        ? isNumeric
            ? "✓ OK"
            : "! À vérifier"
        : statusToLabel(value);
    const tone = numeric
        ? isNumeric
            ? "ok"
            : "warn"
        : statusToTone(value);

    return (
        <div className="technical-check">
            <div className={`technical-check-icon ${tone}`}>{tone === "ok" ? "✓" : tone === "bad" ? "✕" : "!"}</div>
            <div className="technical-check-content">
                <strong>{label}</strong>
                <span>{status}</span>
            </div>
            <span className={`technical-status ${tone}`}>{status}</span>
        </div>
    );
}
