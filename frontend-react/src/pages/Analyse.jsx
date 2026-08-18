import { useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../components/AuthContext";
import "../style/Analyse.css";

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
*/

const API_URL =
    import.meta.env.VITE_API_URL || "http://localhost:8000";

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

const getToken = () => {
    return (
        localStorage.getItem("token") ||
        localStorage.getItem("TOKEN_KEY") ||
        localStorage.getItem("access_token") ||
        localStorage.getItem("jwt") ||
        ""
    );
};

const normalizeUrl = (url) => {
    let value = String(url || "").trim();

    if (!value) {
        return "";
    }

    if (!/^https?:\/\//i.test(value)) {
        value = `https://${value}`;
    }

    return value;
};

const formatDate = (date) => {
    if (!date) {
        return "Date inconnue";
    }

    try {
        return new Intl.DateTimeFormat("fr-FR", {
            day: "2-digit",
            month: "long",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        }).format(new Date(date));
    } catch {
        return date;
    }
};

const getScoreLabel = (score) => {
    if (score >= 90) return "Excellent";
    if (score >= 75) return "Très bon";
    if (score >= 60) return "Correct";
    if (score >= 40) return "À améliorer";
    return "Faible";
};

const getScoreClass = (score) => {
    if (score >= 90) return "excellent";
    if (score >= 75) return "good";
    if (score >= 60) return "average";
    if (score >= 40) return "warning";
    return "danger";
};

const isTruthy = (value) => {
    return (
        value === true ||
        value === 1 ||
        value === "1" ||
        value === "true"
    );
};

/*
|--------------------------------------------------------------------------
| CALCUL SCORE ON-PAGE
|--------------------------------------------------------------------------
|
| Python ne calcule pas de globalScore.
| Donc on calcule un score spécifique à l'analyse On-Page.
|
*/

const calculateScore = (page = {}, headings = [], images = [], keywords = []) => {
    const checks = [];

    // HTTPS
    checks.push(isTruthy(page.is_https));

    // Title
    const titleLength = Number(page.title_length || 0);

    checks.push(
        Boolean(page.title) &&
        titleLength >= 30 &&
        titleLength <= 65
    );

    // Meta description
    const metaLength = Number(page.meta_length || 0);

    checks.push(
        Boolean(page.meta_description) &&
        metaLength >= 70 &&
        metaLength <= 170
    );

    // Canonical
    checks.push(Boolean(page.canonical_url));

    // Robots
    checks.push(Boolean(page.meta_robots));

    // Language
    checks.push(Boolean(page.lang_attribute));

    // H1
    checks.push(
        Number(page.h1_count || 0) === 1 &&
        page.h1_is_unique !== false
    );

    // Content
    checks.push(Number(page.word_count || 0) >= 300);

    // Structured data
    checks.push(isTruthy(page.has_structured_data));

    // Viewport
    checks.push(isTruthy(page.viewport_meta));

    // Images ALT
    const imagesCount = Number(page.images_count || 0);
    const imagesWithoutAlt = Number(
        page.images_without_alt_count || 0
    );

    if (imagesCount > 0) {
        checks.push(imagesWithoutAlt === 0);
    } else {
        checks.push(true);
    }

    // Internal links
    checks.push(Number(page.internal_links_count || 0) > 0);

    // Response time
    const responseTime = Number(page.response_time_ms || 0);

    if (responseTime > 0) {
        checks.push(responseTime <= 1000);
    } else {
        checks.push(true);
    }

    if (!checks.length) {
        return 0;
    }

    const passed = checks.filter(Boolean).length;

    return Math.round((passed / checks.length) * 100);
};

/*
|--------------------------------------------------------------------------
| COMPONENT
|--------------------------------------------------------------------------
*/

export default function Analyse() {
    const navigate = useNavigate();
    const { user } = useAuth();

    const [url, setUrl] = useState("");
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState("");
    const [report, setReport] = useState(null);

    /*
    |--------------------------------------------------------------------------
    | ANALYSE
    |--------------------------------------------------------------------------
    */

    const handleAnalyze = async (event) => {
        event?.preventDefault();

        setError("");

        const cleanUrl = normalizeUrl(url);

        if (!cleanUrl) {
            setError("Veuillez entrer l’URL de votre site.");
            return;
        }

        try {
            new URL(cleanUrl);
        } catch {
            setError("Veuillez entrer une URL valide.");
            return;
        }

        const token = getToken();

        if (!token) {
            setError(
                "Votre session a expiré. Veuillez vous reconnecter."
            );
            return;
        }

        setLoading(true);
        setReport(null);

        try {
            const response = await fetch(
                `${API_URL}/api/audit-onpage`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        Authorization: `Bearer ${token}`,
                    },
                    body: JSON.stringify({
                        url: cleanUrl,
                    }),
                }
            );

            let data = null;

            try {
                data = await response.json();
            } catch {
                throw new Error(
                    "Le serveur a retourné une réponse invalide."
                );
            }

            if (!response.ok) {
                throw new Error(
                    data?.message ||
                    data?.error_message ||
                    "L’analyse a échoué."
                );
            }

            if (data?.status !== "success") {
                throw new Error(
                    data?.message ||
                    data?.error_message ||
                    "L’analyse n’a pas pu être terminée."
                );
            }

            /*
             * Symfony response:
             *
             * data:
             * {
             *   audit_id,
             *   audit_page_id,
             *   site,
             *   url,
             *   page,
             *   headings,
             *   images,
             *   keyword_density
             * }
             */

            const result = data?.data || {};

            const page = result?.page || {};
            const headings = Array.isArray(result?.headings)
                ? result.headings
                : [];
            const images = Array.isArray(result?.images)
                ? result.images
                : [];
            const keywords = Array.isArray(result?.keyword_density)
                ? result.keyword_density
                : [];

            const score = calculateScore(
                page,
                headings,
                images,
                keywords
            );

            setReport({
                auditId: result?.audit_id || null,
                auditPageId: result?.audit_page_id || null,
                site: result?.site || cleanUrl,
                url: result?.url || cleanUrl,
                page,
                headings,
                images,
                keywords,
                score,
                createdAt:
                    page?.created_at ||
                    new Date().toISOString(),
            });

            /*
             * Scroll automatique vers le rapport
             */
            setTimeout(() => {
                document
                    .getElementById("analyse-report")
                    ?.scrollIntoView({
                        behavior: "smooth",
                        block: "start",
                    });
            }, 100);

        } catch (err) {
            console.error("ON-PAGE ANALYSIS ERROR:", err);

            setError(
                err?.message ||
                "Une erreur est survenue pendant l’analyse."
            );
        } finally {
            setLoading(false);
        }
    };

    /*
    |--------------------------------------------------------------------------
    | RESET
    |--------------------------------------------------------------------------
    */

    const handleNewAnalysis = () => {
        setReport(null);
        setError("");
        setUrl("");

        window.scrollTo({
            top: 0,
            behavior: "smooth",
        });
    };

    /*
    |--------------------------------------------------------------------------
    | DERIVED DATA
    |--------------------------------------------------------------------------
    */

    const stats = useMemo(() => {
        if (!report) {
            return null;
        }

        const page = report.page || {};

        const imagesCount = Number(
            page.images_count || report.images.length || 0
        );

        const imagesWithoutAlt = Number(
            page.images_without_alt_count ||
            report.images.filter(
                (image) => !isTruthy(image.has_alt)
            ).length ||
            0
        );

        const headingsCount = report.headings.length;

        const h1Count = Number(
            page.h1_count || 0
        );

        const words = Number(
            page.word_count || 0
        );

        const internalLinks = Number(
            page.internal_links_count || 0
        );

        const externalLinks = Number(
            page.external_links_count || 0
        );

        return {
            imagesCount,
            imagesWithoutAlt,
            headingsCount,
            h1Count,
            words,
            internalLinks,
            externalLinks,
        };
    }, [report]);

    /*
    |--------------------------------------------------------------------------
    | CHECKS
    |--------------------------------------------------------------------------
    */

    const checks = useMemo(() => {
        if (!report) {
            return [];
        }

        const page = report.page || {};

        const titleLength = Number(
            page.title_length || 0
        );

        const metaLength = Number(
            page.meta_length || 0
        );

        const responseTime = Number(
            page.response_time_ms || 0
        );

        return [
            {
                label: "HTTPS",
                value: isTruthy(page.is_https),
                detail: isTruthy(page.is_https)
                    ? "Connexion sécurisée"
                    : "Le site n'utilise pas HTTPS",
            },
            {
                label: "Title",
                value:
                    Boolean(page.title) &&
                    titleLength >= 30 &&
                    titleLength <= 65,
                detail: page.title
                    ? `${titleLength} caractères`
                    : "Title absent",
            },
            {
                label: "Meta description",
                value:
                    Boolean(page.meta_description) &&
                    metaLength >= 70 &&
                    metaLength <= 170,
                detail: page.meta_description
                    ? `${metaLength} caractères`
                    : "Meta description absente",
            },
            {
                label: "Canonical",
                value: Boolean(page.canonical_url),
                detail: page.canonical_url
                    ? "Canonical détectée"
                    : "Canonical absente",
            },
            {
                label: "Robots",
                value: Boolean(page.meta_robots),
                detail: page.meta_robots
                    ? page.meta_robots
                    : "Directive robots non trouvée",
            },
            {
                label: "Langue",
                value: Boolean(page.lang_attribute),
                detail: page.lang_attribute
                    ? `Langue : ${page.lang_attribute}`
                    : "Attribut lang absent",
            },
            {
                label: "H1",
                value:
                    Number(page.h1_count || 0) === 1 &&
                    page.h1_is_unique !== false,
                detail:
                    Number(page.h1_count || 0) === 1
                        ? "Un H1 unique"
                        : `${page.h1_count || 0} H1 détecté(s)`,
            },
            {
                label: "Viewport",
                value: isTruthy(page.viewport_meta),
                detail: isTruthy(page.viewport_meta)
                    ? "Viewport mobile détecté"
                    : "Viewport absent",
            },
            {
                label: "Structured Data",
                value: isTruthy(page.has_structured_data),
                detail: isTruthy(page.has_structured_data)
                    ? "Données structurées détectées"
                    : "Aucune donnée structurée",
            },
            {
                label: "Contenu",
                value: Number(page.word_count || 0) >= 300,
                detail: `${page.word_count || 0} mots`,
            },
            {
                label: "Images ALT",
                value:
                    Number(page.images_without_alt_count || 0) === 0,
                detail:
                    Number(page.images_without_alt_count || 0) === 0
                        ? "Toutes les images ont un ALT"
                        : `${page.images_without_alt_count} image(s) sans ALT`,
            },
            {
                label: "Performance",
                value:
                    responseTime === 0 ||
                    responseTime <= 1000,
                detail:
                    responseTime > 0
                        ? `${responseTime} ms`
                        : "Temps non disponible",
            },
        ];
    }, [report]);

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    return (
        <main className="analyse-page">

            {/* =================================================
                HERO
            ================================================= */}

            <section className="analyse-hero">

                <div className="analyse-hero-content">

                    <div className="analyse-eyebrow">
                        SEO ON-PAGE
                    </div>

                    <h1>
                        Analysez votre
                        <span> site web</span>
                    </h1>

                    <p className="analyse-intro">
                        Obtenez un rapport détaillé sur la structure,
                        le contenu et les éléments techniques de votre page.
                    </p>

                    <div className="analyse-hero-actions">
                        <button
                            type="button"
                            className="analyse-history-btn"
                            onClick={() =>
                                navigate("/analyse-historique")
                            }
                        >
                            <span>↗</span>
                            Historique
                        </button>
                    </div>

                </div>

            </section>

            {/* =================================================
                ANALYSIS FORM
            ================================================= */}

            {!report && (
                <section className="analyse-launch">

                    <div className="section-kicker">
                        <span>✦</span>
                        NOUVELLE ANALYSE
                    </div>

                    <div className="launch-heading">
                        <h2>
                            Lancez une nouvelle analyse
                        </h2>

                        <p>
                            Entrez l’URL du site que vous souhaitez analyser.
                        </p>
                    </div>

                    <form
                        className="analyse-form-card"
                        onSubmit={handleAnalyze}
                    >

                        <div className="url-input-wrapper">

                            <span className="url-icon">
                                ↗
                            </span>

                            <input
                                type="text"
                                value={url}
                                onChange={(e) =>
                                    setUrl(e.target.value)
                                }
                                placeholder="https://www.exemple.com"
                                disabled={loading}
                                autoComplete="url"
                            />

                        </div>

                        <button
                            type="submit"
                            className="analyse-submit-btn"
                            disabled={loading}
                        >

                            {loading ? (
                                <>
                                    <span className="button-spinner" />
                                    Analyse en cours...
                                </>
                            ) : (
                                <>
                                    Analyser le site
                                    <span>→</span>
                                </>
                            )}

                        </button>

                    </form>

                    {error && (
                        <div className="analyse-error">
                            <div className="error-icon">
                                !
                            </div>

                            <div>
                                <strong>
                                    Analyse impossible
                                </strong>

                                <p>
                                    {error}
                                </p>
                            </div>
                        </div>
                    )}

                    {/* FEATURES */}

                    <div className="analyse-features">

                        <article className="feature-card">

                            <div className="feature-number">
                                01
                            </div>

                            <div className="feature-icon">
                                ◈
                            </div>

                            <h3>
                                SEO technique
                            </h3>

                            <p>
                                Title, meta description, canonical,
                                robots, HTTPS et viewport.
                            </p>

                        </article>

                        <article className="feature-card">

                            <div className="feature-number">
                                02
                            </div>

                            <div className="feature-icon">
                                ◇
                            </div>

                            <h3>
                                Contenu
                            </h3>

                            <p>
                                Word count, H1, headings, liens
                                internes et externes.
                            </p>

                        </article>

                        <article className="feature-card">

                            <div className="feature-number">
                                03
                            </div>

                            <div className="feature-icon">
                                ◎
                            </div>

                            <h3>
                                Images & Keywords
                            </h3>

                            <p>
                                ALT des images et densité
                                des mots-clés détectés.
                            </p>

                        </article>

                    </div>

                </section>
            )}

            {/* =================================================
                REPORT
            ================================================= */}

            {report && (
                <section
                    className="analyse-report"
                    id="analyse-report"
                >

                    {/* REPORT HEADER */}

                    <div className="report-header">

                        <div>

                            <div className="report-eyebrow">
                                RAPPORT SEO ON-PAGE
                            </div>

                            <h2>
                                Rapport d’analyse
                            </h2>

                            <div className="report-url">
                                <span>↗</span>
                                {report.url}
                            </div>

                            <div className="report-date">
                                Analyse effectuée le{" "}
                                {formatDate(report.createdAt)}
                            </div>

                        </div>

                        <div className="report-actions">

                            <button
                                type="button"
                                className="report-secondary-btn"
                                onClick={handleNewAnalysis}
                            >
                                + Nouvelle analyse
                            </button>

                            <button
                                type="button"
                                className="report-primary-btn"
                                onClick={() =>
                                    navigate(
                                        "/analyse-historique"
                                    )
                                }
                            >
                                Historique →
                            </button>

                        </div>

                    </div>

                    {/* SCORE */}

                    <div className="score-section">

                        <div className="score-card">

                            <div className="score-circle-wrapper">

                                <div
                                    className={`score-circle ${getScoreClass(
                                        report.score
                                    )}`}
                                    style={{
                                        "--score":
                                            `${report.score * 3.6}deg`,
                                    }}
                                >
                                    <div className="score-circle-inner">

                                        <strong>
                                            {report.score}
                                        </strong>

                                        <span>
                                            / 100
                                        </span>

                                    </div>
                                </div>

                            </div>

                            <div className="score-information">

                                <div className="score-label">
                                    SCORE ON-PAGE
                                </div>

                                <h3>
                                    {getScoreLabel(
                                        report.score
                                    )}
                                </h3>

                                <p>
                                    Votre page a obtenu un score de{" "}
                                    <strong>
                                        {report.score}/100
                                    </strong>{" "}
                                    sur les critères analysés.
                                </p>

                            </div>

                        </div>

                    </div>

                    {/* QUICK METRICS */}

                    <div className="report-metrics">

                        <div className="metric-card">

                            <span className="metric-label">
                                TITLE
                            </span>

                            <strong>
                                {report.page.title_length || 0}
                            </strong>

                            <small>
                                caractères
                            </small>

                        </div>

                        <div className="metric-card">

                            <span className="metric-label">
                                H1
                            </span>

                            <strong>
                                {stats.h1Count}
                            </strong>

                            <small>
                                titre principal
                            </small>

                        </div>

                        <div className="metric-card">

                            <span className="metric-label">
                                CONTENU
                            </span>

                            <strong>
                                {stats.words.toLocaleString(
                                    "fr-FR"
                                )}
                            </strong>

                            <small>
                                mots
                            </small>

                        </div>

                        <div className="metric-card">

                            <span className="metric-label">
                                IMAGES
                            </span>

                            <strong>
                                {stats.imagesCount}
                            </strong>

                            <small>
                                {stats.imagesWithoutAlt} sans ALT
                            </small>

                        </div>

                        <div className="metric-card">

                            <span className="metric-label">
                                LIENS
                            </span>

                            <strong>
                                {stats.internalLinks}
                            </strong>

                            <small>
                                liens internes
                            </small>

                        </div>

                        <div className="metric-card">

                            <span className="metric-label">
                                TEMPS
                            </span>

                            <strong>
                                {report.page.response_time_ms || 0}
                            </strong>

                            <small>
                                ms
                            </small>

                        </div>

                    </div>

                    {/* SEO CHECKS */}

                    <section className="report-section">

                        <div className="report-section-header">

                            <div>
                                <span>
                                    01
                                </span>

                                <h3>
                                    SEO technique
                                </h3>
                            </div>

                            <p>
                                Les éléments techniques essentiels
                                de votre page.
                            </p>

                        </div>

                        <div className="checks-grid">

                            {checks.map((check, index) => (
                                <div
                                    className={`check-card ${
                                        check.value
                                            ? "check-pass"
                                            : "check-fail"
                                    }`}
                                    key={`${check.label}-${index}`}
                                >

                                    <div className="check-status">
                                        {check.value ? "✓" : "!"}
                                    </div>

                                    <div className="check-content">

                                        <strong>
                                            {check.label}
                                        </strong>

                                        <span>
                                            {check.detail}
                                        </span>

                                    </div>

                                </div>
                            ))}

                        </div>

                    </section>

                    {/* CONTENT */}

                    <section className="report-section">

                        <div className="report-section-header">

                            <div>
                                <span>
                                    02
                                </span>

                                <h3>
                                    Structure & contenu
                                </h3>
                            </div>

                            <p>
                                Analyse de la structure éditoriale
                                et des éléments HTML.
                            </p>

                        </div>

                        <div className="content-overview">

                            <div className="overview-card">

                                <span>
                                    HEADINGS
                                </span>

                                <strong>
                                    {stats.headingsCount}
                                </strong>

                                <small>
                                    titres détectés
                                </small>

                            </div>

                            <div className="overview-card">

                                <span>
                                    H1
                                </span>

                                <strong>
                                    {stats.h1Count}
                                </strong>

                                <small>
                                    titre principal
                                </small>

                            </div>

                            <div className="overview-card">

                                <span>
                                    WORDS
                                </span>

                                <strong>
                                    {stats.words.toLocaleString(
                                        "fr-FR"
                                    )}
                                </strong>

                                <small>
                                    mots analysés
                                </small>

                            </div>

                            <div className="overview-card">

                                <span>
                                    LINKS
                                </span>

                                <strong>
                                    {stats.internalLinks +
                                        stats.externalLinks}
                                </strong>

                                <small>
                                    liens détectés
                                </small>

                            </div>

                        </div>

                        {/* HEADINGS LIST */}

                        {report.headings.length > 0 && (
                            <div className="detail-box">

                                <div className="detail-box-header">
                                    <h4>
                                        Structure des headings
                                    </h4>

                                    <span>
                                        {report.headings.length}
                                    </span>
                                </div>

                                <div className="headings-list">

                                    {report.headings
                                        .slice(0, 30)
                                        .map((heading, index) => (
                                            <div
                                                className="heading-row"
                                                key={`${heading.heading_level}-${index}`}
                                            >

                                                <span
                                                    className={`heading-tag ${String(
                                                        heading.heading_level
                                                    ).toLowerCase()}`}
                                                >
                                                    {String(
                                                        heading.heading_level
                                                    ).toUpperCase()}
                                                </span>

                                                <span className="heading-text">
                                                    {heading.content ||
                                                        "Sans contenu"}
                                                </span>

                                                <span className="heading-position">
                                                    #{heading.position ||
                                                        index + 1}
                                                </span>

                                            </div>
                                        ))}

                                </div>

                            </div>
                        )}

                    </section>

                    {/* IMAGES */}

                    <section className="report-section">

                        <div className="report-section-header">

                            <div>
                                <span>
                                    03
                                </span>

                                <h3>
                                    Images
                                </h3>
                            </div>

                            <p>
                                Vérification des images et de leurs
                                attributs ALT.
                            </p>

                        </div>

                        <div className="image-summary">

                            <div className="image-stat">
                                <strong>
                                    {stats.imagesCount}
                                </strong>

                                <span>
                                    Total images
                                </span>
                            </div>

                            <div className="image-stat success">
                                <strong>
                                    {Math.max(
                                        0,
                                        stats.imagesCount -
                                            stats.imagesWithoutAlt
                                    )}
                                </strong>

                                <span>
                                    Avec ALT
                                </span>
                            </div>

                            <div className="image-stat danger">
                                <strong>
                                    {stats.imagesWithoutAlt}
                                </strong>

                                <span>
                                    Sans ALT
                                </span>
                            </div>

                        </div>

                        {report.images.length > 0 && (
                            <div className="images-table">

                                <div className="images-table-head">
                                    <span>
                                        IMAGE
                                    </span>

                                    <span>
                                        TYPE
                                    </span>

                                    <span>
                                        ALT
                                    </span>
                                </div>

                                {report.images
                                    .slice(0, 25)
                                    .map((image, index) => (
                                        <div
                                            className="image-row"
                                            key={`${image.image_url}-${index}`}
                                        >

                                            <div className="image-url">
                                                <span className="image-placeholder">
                                                    IMG
                                                </span>

                                                <span>
                                                    {image.image_url ||
                                                        "URL inconnue"}
                                                </span>
                                            </div>

                                            <span>
                                                {image.image_type
                                                    ? String(
                                                          image.image_type
                                                      ).toUpperCase()
                                                    : "—"}
                                            </span>

                                            <span
                                                className={
                                                    isTruthy(
                                                        image.has_alt
                                                    )
                                                        ? "alt-ok"
                                                        : "alt-missing"
                                                }
                                            >
                                                {isTruthy(
                                                    image.has_alt
                                                )
                                                    ? "✓ Présent"
                                                    : "⚠ Manquant"}
                                            </span>

                                        </div>
                                    ))}

                            </div>
                        )}

                    </section>

                    {/* KEYWORDS */}

                    <section className="report-section">

                        <div className="report-section-header">

                            <div>
                                <span>
                                    04
                                </span>

                                <h3>
                                    Keyword density
                                </h3>
                            </div>

                            <p>
                                Les mots-clés les plus présents
                                dans le contenu analysé.
                            </p>

                        </div>

                        {report.keywords.length > 0 ? (
                            <div className="keywords-table">

                                <div className="keywords-table-head">
                                    <span>
                                        MOT-CLÉ
                                    </span>

                                    <span>
                                        OCCURRENCES
                                    </span>

                                    <span>
                                        DENSITÉ
                                    </span>
                                </div>

                                {report.keywords
                                    .slice(0, 30)
                                    .map((keyword, index) => (
                                        <div
                                            className="keyword-row"
                                            key={`${keyword.keyword}-${index}`}
                                        >

                                            <strong>
                                                {keyword.keyword ||
                                                    "—"}
                                            </strong>

                                            <span>
                                                {keyword.count ??
                                                    "—"}
                                            </span>

                                            <span className="density-value">
                                                {keyword.density ??
                                                    keyword.percentage ??
                                                    "—"}
                                                {typeof (
                                                    keyword.density ??
                                                    keyword.percentage
                                                ) === "number"
                                                    ? "%"
                                                    : ""}
                                            </span>

                                        </div>
                                    ))}

                            </div>
                        ) : (
                            <div className="empty-report">
                                <span>
                                    ◌
                                </span>

                                <p>
                                    Aucun mot-clé exploitable n’a
                                    été détecté.
                                </p>
                            </div>
                        )}

                    </section>

                    {/* FOOTER */}

                    <div className="report-footer">

                        <div>
                            <span className="footer-dot" />

                            Analyse enregistrée
                        </div>

                        {report.auditId && (
                            <span>
                                Audit #{report.auditId}
                            </span>
                        )}

                    </div>

                </section>
            )}

        </main>
    );
}