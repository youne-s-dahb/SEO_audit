import { useMemo, useState } from "react";
import html2pdf from "html2pdf.js";
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
*/

const calculateScore = (
    page = {},
    headings = [],
    images = [],
    keywords = []
) => {
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
    checks.push(
        Number(page.internal_links_count || 0) > 0
    );

    // Response time
    const responseTime = Number(
        page.response_time_ms || 0
    );

    if (responseTime > 0) {
        checks.push(responseTime <= 1000);
    } else {
        checks.push(true);
    }

    if (!checks.length) {
        return 0;
    }

    const passed = checks.filter(Boolean).length;

    return Math.round(
        (passed / checks.length) * 100
    );
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

    const [downloadingPdf, setDownloadingPdf] =
        useState(false);

    const [downloadError, setDownloadError] =
        useState("");

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
            setError(
                "Veuillez entrer l’URL de votre site."
            );
            return;
        }

        try {
            new URL(cleanUrl);
        } catch {
            setError(
                "Veuillez entrer une URL valide."
            );
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
                        "Content-Type":
                            "application/json",

                        Accept:
                            "application/json",

                        Authorization:
                            `Bearer ${token}`,
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

            const result = data?.data || {};

            const page =
                result?.page || {};

            const headings =
                Array.isArray(result?.headings)
                    ? result.headings
                    : [];

            const images =
                Array.isArray(result?.images)
                    ? result.images
                    : [];

            const keywords =
                Array.isArray(
                    result?.keyword_density
                )
                    ? result.keyword_density
                    : [];

            const score = calculateScore(
                page,
                headings,
                images,
                keywords
            );

            setReport({
                auditId:
                    result?.audit_id || null,

                auditPageId:
                    result?.audit_page_id || null,

                site:
                    result?.site || cleanUrl,

                url:
                    result?.url || cleanUrl,

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
            |--------------------------------------------------------------
            | Scroll automatique
            |--------------------------------------------------------------
            */

            setTimeout(() => {
                document
                    .getElementById(
                        "analyse-report"
                    )
                    ?.scrollIntoView({
                        behavior: "smooth",
                        block: "start",
                    });
            }, 100);

        } catch (err) {
            console.error(
                "ON-PAGE ANALYSIS ERROR:",
                err
            );

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
    | DOWNLOAD PDF — REACT ONLY
    |--------------------------------------------------------------------------
    */

   const handleDownloadPdf = async () => {
    const element = document.getElementById("analyse-report");

    if (!element) {
        setDownloadError("Le rapport est introuvable.");
        return;
    }

    setDownloadError("");
    setDownloadingPdf(true);

    try {
        /*
        |--------------------------------------------------------------------------
        | CLONER LE RAPPORT
        |--------------------------------------------------------------------------
        */

        const pdfContainer = document.createElement("div");

        pdfContainer.style.position = "fixed";
        pdfContainer.style.left = "-100000px";
        pdfContainer.style.top = "0";
        pdfContainer.style.width = "794px";
        pdfContainer.style.background = "#ffffff";
        pdfContainer.style.zIndex = "-9999";
        pdfContainer.style.padding = "0";
        pdfContainer.style.margin = "0";

        const clonedReport = element.cloneNode(true);

        clonedReport.id = "pdf-report";

        pdfContainer.appendChild(clonedReport);
        document.body.appendChild(pdfContainer);

        /*
        |--------------------------------------------------------------------------
        | STYLE PDF
        |--------------------------------------------------------------------------
        */

        const style = document.createElement("style");

        style.id = "pdf-report-style";

        style.innerHTML = `
            #pdf-report {
                width: 794px !important;
                max-width: 794px !important;
                min-width: 794px !important;

                margin: 0 !important;
                padding: 42px 46px !important;

                background: #ffffff !important;
                color: #111111 !important;

                font-family:
                    Inter,
                    Arial,
                    Helvetica,
                    sans-serif !important;

                box-sizing: border-box !important;

                box-shadow: none !important;
                border: none !important;

                line-height: 1.5 !important;
            }

            #pdf-report *,
            #pdf-report *::before,
            #pdf-report *::after {
                box-sizing: border-box !important;
            }

            /* ---------------------------------------------------------
               HIDE WEB ACTIONS
            --------------------------------------------------------- */

            #pdf-report .report-actions,
            #pdf-report .analyse-error {
                display: none !important;
            }

            /* ---------------------------------------------------------
               HEADER
            --------------------------------------------------------- */

            #pdf-report .report-header {
                display: flex !important;
                justify-content: space-between !important;
                align-items: flex-start !important;

                width: 100% !important;

                padding: 0 0 28px 0 !important;
                margin: 0 0 30px 0 !important;

                background: #ffffff !important;

                border-bottom: 2px solid #111111 !important;

                box-shadow: none !important;
            }

            #pdf-report .report-eyebrow {
                font-size: 11px !important;
                font-weight: 700 !important;
                letter-spacing: 2px !important;
                color: #555555 !important;

                margin-bottom: 8px !important;
            }

            #pdf-report .report-header h2 {
                margin: 0 0 12px 0 !important;

                font-size: 30px !important;
                line-height: 1.15 !important;

                color: #111111 !important;
                font-weight: 800 !important;
            }

            #pdf-report .report-url {
                max-width: 600px !important;

                font-size: 12px !important;
                color: #333333 !important;

                word-break: break-all !important;
                overflow-wrap: anywhere !important;

                margin-bottom: 6px !important;
            }

            #pdf-report .report-date {
                font-size: 11px !important;
                color: #777777 !important;
            }

            /* ---------------------------------------------------------
               SCORE
            --------------------------------------------------------- */

            #pdf-report .score-section {
                width: 100% !important;

                margin: 0 0 28px 0 !important;
                padding: 0 !important;

                background: #ffffff !important;

                box-shadow: none !important;
            }

            #pdf-report .score-card {
                display: flex !important;
                align-items: center !important;

                width: 100% !important;

                min-height: 170px !important;

                padding: 26px !important;

                background: #f8f8f8 !important;

                border: 1px solid #dddddd !important;
                border-radius: 14px !important;

                box-shadow: none !important;

                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            #pdf-report .score-circle-wrapper {
                flex: 0 0 135px !important;

                width: 135px !important;
                height: 135px !important;

                margin-right: 30px !important;
            }

            #pdf-report .score-circle {
                width: 135px !important;
                height: 135px !important;

                border-radius: 50% !important;

                display: flex !important;
                align-items: center !important;
                justify-content: center !important;

                background: #eeeeee !important;

                position: relative !important;
            }

            #pdf-report .score-circle::before {
                content: "" !important;

                position: absolute !important;

                inset: 8px !important;

                border-radius: 50% !important;

                background: #ffffff !important;
            }

            #pdf-report .score-circle-inner {
                position: relative !important;
                z-index: 2 !important;

                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;

                width: 100% !important;
                height: 100% !important;
            }

            #pdf-report .score-circle-inner strong {
                font-size: 34px !important;
                line-height: 1 !important;

                color: #111111 !important;
                font-weight: 800 !important;
            }

            #pdf-report .score-circle-inner span {
                margin-top: 5px !important;

                font-size: 11px !important;

                color: #777777 !important;
            }

            #pdf-report .score-information {
                flex: 1 !important;
            }

            #pdf-report .score-label {
                font-size: 10px !important;
                font-weight: 700 !important;
                letter-spacing: 1.5px !important;

                color: #777777 !important;

                margin-bottom: 5px !important;
            }

            #pdf-report .score-information h3 {
                margin: 0 0 8px 0 !important;

                font-size: 22px !important;

                color: #111111 !important;
                font-weight: 800 !important;
            }

            #pdf-report .score-information p {
                margin: 0 !important;

                font-size: 12px !important;

                color: #555555 !important;
            }

            /* ---------------------------------------------------------
               METRICS
            --------------------------------------------------------- */

            #pdf-report .report-metrics {
                display: grid !important;

                grid-template-columns:
                    repeat(3, 1fr) !important;

                gap: 12px !important;

                margin: 0 0 34px 0 !important;

                width: 100% !important;
            }

            #pdf-report .metric-card {
                min-height: 92px !important;

                padding: 15px !important;

                background: #ffffff !important;

                border: 1px solid #dddddd !important;
                border-radius: 10px !important;

                box-shadow: none !important;

                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            #pdf-report .metric-label {
                display: block !important;

                font-size: 9px !important;
                font-weight: 700 !important;
                letter-spacing: 1px !important;

                color: #777777 !important;

                margin-bottom: 8px !important;
            }

            #pdf-report .metric-card strong {
                display: inline-block !important;

                font-size: 23px !important;

                color: #111111 !important;
                font-weight: 800 !important;
            }

            #pdf-report .metric-card small {
                display: block !important;

                font-size: 10px !important;

                color: #777777 !important;
            }

            /* ---------------------------------------------------------
               SECTIONS
            --------------------------------------------------------- */

            #pdf-report .report-section {
                width: 100% !important;

                margin: 0 0 34px 0 !important;
                padding: 0 !important;

                background: #ffffff !important;

                box-shadow: none !important;

                break-inside: auto !important;
                page-break-inside: auto !important;
            }

            #pdf-report .report-section-header {
                display: flex !important;
                justify-content: space-between !important;
                align-items: flex-end !important;

                gap: 20px !important;

                padding: 0 0 12px 0 !important;
                margin: 0 0 16px 0 !important;

                border-bottom: 1px solid #cccccc !important;
            }

            #pdf-report .report-section-header > div {
                display: flex !important;
                align-items: center !important;
                gap: 10px !important;
            }

            #pdf-report .report-section-header > div > span {
                font-size: 10px !important;

                color: #777777 !important;

                font-weight: 700 !important;
            }

            #pdf-report .report-section-header h3 {
                margin: 0 !important;

                font-size: 18px !important;

                color: #111111 !important;

                font-weight: 800 !important;
            }

            #pdf-report .report-section-header p {
                margin: 0 !important;

                max-width: 300px !important;

                text-align: right !important;

                font-size: 10px !important;

                color: #777777 !important;
            }

            /* ---------------------------------------------------------
               CHECKS
            --------------------------------------------------------- */

            #pdf-report .checks-grid {
                display: grid !important;

                grid-template-columns:
                    repeat(2, 1fr) !important;

                gap: 10px !important;

                width: 100% !important;
            }

            #pdf-report .check-card {
                display: flex !important;
                align-items: flex-start !important;

                min-height: 70px !important;

                padding: 13px !important;

                background: #ffffff !important;

                border: 1px solid #dddddd !important;

                border-radius: 9px !important;

                box-shadow: none !important;

                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            #pdf-report .check-status {
                flex: 0 0 25px !important;

                width: 25px !important;
                height: 25px !important;

                display: flex !important;
                align-items: center !important;
                justify-content: center !important;

                border-radius: 50% !important;

                font-size: 12px !important;
                font-weight: 800 !important;

                background: #eeeeee !important;
                color: #111111 !important;

                margin-right: 10px !important;
            }

            #pdf-report .check-content {
                min-width: 0 !important;
            }

            #pdf-report .check-content strong {
                display: block !important;

                margin-bottom: 3px !important;

                font-size: 11px !important;

                color: #111111 !important;
            }

            #pdf-report .check-content span {
                display: block !important;

                font-size: 9px !important;

                color: #666666 !important;

                overflow-wrap: anywhere !important;
            }

            /* ---------------------------------------------------------
               OVERVIEW
            --------------------------------------------------------- */

            #pdf-report .content-overview {
                display: grid !important;

                grid-template-columns:
                    repeat(4, 1fr) !important;

                gap: 10px !important;

                margin-bottom: 16px !important;
            }

            #pdf-report .overview-card {
                padding: 15px !important;

                background: #f8f8f8 !important;

                border: 1px solid #dddddd !important;
                border-radius: 9px !important;

                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            #pdf-report .overview-card span {
                display: block !important;

                font-size: 9px !important;

                font-weight: 700 !important;

                color: #777777 !important;

                margin-bottom: 6px !important;
            }

            #pdf-report .overview-card strong {
                display: block !important;

                font-size: 22px !important;

                color: #111111 !important;

                font-weight: 800 !important;
            }

            #pdf-report .overview-card small {
                display: block !important;

                font-size: 9px !important;

                color: #777777 !important;
            }

            /* ---------------------------------------------------------
               DETAIL BOX
            --------------------------------------------------------- */

            #pdf-report .detail-box {
                width: 100% !important;

                padding: 16px !important;

                border: 1px solid #dddddd !important;

                border-radius: 10px !important;

                background: #ffffff !important;

                box-shadow: none !important;

                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            #pdf-report .detail-box-header {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;

                padding-bottom: 10px !important;
                margin-bottom: 8px !important;

                border-bottom: 1px solid #eeeeee !important;
            }

            #pdf-report .detail-box-header h4 {
                margin: 0 !important;

                font-size: 12px !important;

                color: #111111 !important;
            }

            #pdf-report .detail-box-header span {
                font-size: 10px !important;

                color: #777777 !important;
            }

            #pdf-report .heading-row {
                display: flex !important;
                align-items: center !important;

                gap: 10px !important;

                min-height: 34px !important;

                padding: 6px 0 !important;

                border-bottom: 1px solid #eeeeee !important;

                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            #pdf-report .heading-tag {
                flex: 0 0 35px !important;

                font-size: 9px !important;

                font-weight: 800 !important;

                color: #555555 !important;
            }

            #pdf-report .heading-text {
                flex: 1 !important;

                min-width: 0 !important;

                font-size: 10px !important;

                color: #222222 !important;

                overflow-wrap: anywhere !important;
            }

            #pdf-report .heading-position {
                flex: 0 0 30px !important;

                text-align: right !important;

                font-size: 9px !important;

                color: #999999 !important;
            }

            /* ---------------------------------------------------------
               IMAGES
            --------------------------------------------------------- */

            #pdf-report .image-summary {
                display: grid !important;

                grid-template-columns:
                    repeat(3, 1fr) !important;

                gap: 10px !important;

                margin-bottom: 16px !important;
            }

            #pdf-report .image-stat {
                padding: 16px !important;

                background: #f8f8f8 !important;

                border: 1px solid #dddddd !important;
                border-radius: 9px !important;

                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            #pdf-report .image-stat strong {
                display: block !important;

                font-size: 23px !important;

                color: #111111 !important;
            }

            #pdf-report .image-stat span {
                font-size: 9px !important;

                color: #777777 !important;
            }

            #pdf-report .images-table,
            #pdf-report .keywords-table {
                width: 100% !important;

                border: 1px solid #dddddd !important;

                border-radius: 10px !important;

                overflow: hidden !important;

                background: #ffffff !important;

                box-shadow: none !important;
            }

            #pdf-report .images-table-head,
            #pdf-report .keywords-table-head {
                display: grid !important;

                background: #f3f3f3 !important;

                color: #444444 !important;

                font-size: 9px !important;

                font-weight: 800 !important;

                letter-spacing: .5px !important;

                padding: 10px 12px !important;
            }

            #pdf-report .images-table-head {
                grid-template-columns:
                    2fr 1fr 1fr !important;
            }

            #pdf-report .image-row {
                display: grid !important;

                grid-template-columns:
                    2fr 1fr 1fr !important;

                gap: 10px !important;

                align-items: center !important;

                padding: 9px 12px !important;

                border-top: 1px solid #eeeeee !important;

                font-size: 9px !important;

                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            #pdf-report .image-url {
                display: flex !important;
                align-items: center !important;

                gap: 7px !important;

                min-width: 0 !important;
            }

            #pdf-report .image-url > span:last-child {
                overflow-wrap: anywhere !important;

                word-break: break-all !important;

                color: #333333 !important;
            }

            #pdf-report .image-placeholder {
                flex: 0 0 25px !important;

                font-size: 7px !important;
                font-weight: 800 !important;

                color: #777777 !important;
            }

            /* ---------------------------------------------------------
               KEYWORDS
            --------------------------------------------------------- */

            #pdf-report .keywords-table-head {
                grid-template-columns:
                    2fr 1fr 1fr !important;
            }

            #pdf-report .keyword-row {
                display: grid !important;

                grid-template-columns:
                    2fr 1fr 1fr !important;

                gap: 10px !important;

                align-items: center !important;

                min-height: 36px !important;

                padding: 8px 12px !important;

                border-top: 1px solid #eeeeee !important;

                font-size: 10px !important;

                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            #pdf-report .keyword-row strong {
                color: #111111 !important;

                overflow-wrap: anywhere !important;
            }

            #pdf-report .keyword-row span {
                color: #555555 !important;
            }

            #pdf-report .density-value {
                font-weight: 700 !important;

                color: #111111 !important;
            }

            /* ---------------------------------------------------------
               EMPTY
            --------------------------------------------------------- */

            #pdf-report .empty-report {
                padding: 30px !important;

                text-align: center !important;

                border: 1px solid #dddddd !important;
                border-radius: 10px !important;

                background: #fafafa !important;
            }

            #pdf-report .empty-report span {
                display: block !important;

                font-size: 25px !important;

                color: #999999 !important;

                margin-bottom: 8px !important;
            }

            #pdf-report .empty-report p {
                margin: 0 !important;

                font-size: 11px !important;

                color: #777777 !important;
            }

            /* ---------------------------------------------------------
               FOOTER
            --------------------------------------------------------- */

            #pdf-report .report-footer {
                display: flex !important;

                justify-content: space-between !important;
                align-items: center !important;

                margin-top: 35px !important;
                padding-top: 15px !important;

                border-top: 1px solid #dddddd !important;

                font-size: 9px !important;

                color: #777777 !important;

                background: #ffffff !important;

                box-shadow: none !important;
            }

            #pdf-report .footer-dot {
                display: inline-block !important;

                width: 7px !important;
                height: 7px !important;

                margin-right: 6px !important;

                border-radius: 50% !important;

                background: #111111 !important;
            }

            /* ---------------------------------------------------------
               PRINT BREAKS
            --------------------------------------------------------- */

            #pdf-report h1,
            #pdf-report h2,
            #pdf-report h3,
            #pdf-report h4 {
                break-after: avoid !important;
                page-break-after: avoid !important;
            }

            #pdf-report .report-section-header {
                break-after: avoid !important;
                page-break-after: avoid !important;
            }

            #pdf-report .metric-card,
            #pdf-report .check-card,
            #pdf-report .overview-card,
            #pdf-report .detail-box,
            #pdf-report .image-stat,
            #pdf-report .image-row,
            #pdf-report .keyword-row,
            #pdf-report .score-card {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            /* ---------------------------------------------------------
               REMOVE WEB ANIMATIONS
            --------------------------------------------------------- */

            #pdf-report *,
            #pdf-report *::before,
            #pdf-report *::after {
                animation: none !important;
                transition: none !important;
            }
        `;

        pdfContainer.appendChild(style);

        /*
        |--------------------------------------------------------------------------
        | ATTENDRE LE RENDU
        |--------------------------------------------------------------------------
        */

        await new Promise((resolve) => {
            requestAnimationFrame(() => {
                requestAnimationFrame(resolve);
            });
        });

        /*
        |--------------------------------------------------------------------------
        | PDF OPTIONS
        |--------------------------------------------------------------------------
        */

        const options = {
            margin: [8, 8, 10, 8],

            filename:
                `rapport-audit-${report?.auditId || "seo"}.pdf`,

            image: {
                type: "jpeg",
                quality: 0.98,
            },

            html2canvas: {
                scale: 2,

                useCORS: true,

                allowTaint: false,

                backgroundColor: "#ffffff",

                logging: false,

                imageTimeout: 15000,

                windowWidth: 794,

                scrollX: 0,

                scrollY: 0,
            },

            jsPDF: {
                unit: "mm",

                format: "a4",

                orientation: "portrait",

                compress: true,
            },

            pagebreak: {
                mode: [
                    "css",
                    "legacy",
                ],

                avoid: [
                    ".score-card",
                    ".metric-card",
                    ".check-card",
                    ".overview-card",
                    ".detail-box",
                    ".image-stat",
                    ".image-row",
                    ".keyword-row",
                    ".report-section-header",
                ],
            },
        };

        /*
        |--------------------------------------------------------------------------
        | GENERATE PDF
        |--------------------------------------------------------------------------
        */

        await html2pdf()
            .set(options)
            .from(clonedReport)
            .toPdf()
            .get("pdf")
            .then((pdf) => {
                const totalPages =
                    pdf.internal.getNumberOfPages();

                /*
                |--------------------------------------------------------------
                | NUMÉRO DE PAGE
                |--------------------------------------------------------------
                */

                for (
                    let pageNumber = 1;
                    pageNumber <= totalPages;
                    pageNumber++
                ) {
                    pdf.setPage(pageNumber);

                    const pageWidth =
                        pdf.internal.pageSize.getWidth();

                    const pageHeight =
                        pdf.internal.pageSize.getHeight();

                    /*
                    | Footer line
                    */

                    pdf.setDrawColor(
                        220,
                        220,
                        220
                    );

                    pdf.setLineWidth(0.2);

                    pdf.line(
                        10,
                        pageHeight - 12,
                        pageWidth - 10,
                        pageHeight - 12
                    );

                    /*
                    | Footer text
                    */

                    pdf.setFontSize(7);

                    pdf.setTextColor(
                        120,
                        120,
                        120
                    );

                    pdf.text(
                        "SEO Audit Platform",
                        10,
                        pageHeight - 7
                    );

                    pdf.text(
                        `Page ${pageNumber} / ${totalPages}`,
                        pageWidth - 10,
                        pageHeight - 7,
                        {
                            align: "right",
                        }
                    );
                }
            })
            .save();

        /*
        |--------------------------------------------------------------------------
        | NETTOYAGE
        |--------------------------------------------------------------------------
        */

        document.body.removeChild(
            pdfContainer
        );

    } catch (err) {
        console.error(
            "PDF ERROR:",
            err
        );

        /*
        |--------------------------------------------------------------------------
        | NETTOYAGE EN CAS D'ERREUR
        |--------------------------------------------------------------------------
        */

        const existing =
            document.getElementById(
                "pdf-report-container"
            );

        if (existing) {
            existing.remove();
        }

        setDownloadError(
            err?.message ||
            "Impossible de générer le PDF."
        );

    } finally {
        setDownloadingPdf(false);
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
        setDownloadError("");

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

        const page =
            report.page || {};

        const imagesCount =
            Number(
                page.images_count ||
                report.images.length ||
                0
            );

        const imagesWithoutAlt =
            Number(
                page.images_without_alt_count ||
                report.images.filter(
                    (image) =>
                        !isTruthy(
                            image.has_alt
                        )
                ).length ||
                0
            );

        const headingsCount =
            report.headings.length;

        const h1Count =
            Number(
                page.h1_count || 0
            );

        const words =
            Number(
                page.word_count || 0
            );

        const internalLinks =
            Number(
                page.internal_links_count ||
                0
            );

        const externalLinks =
            Number(
                page.external_links_count ||
                0
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

        const page =
            report.page || {};

        const titleLength =
            Number(
                page.title_length || 0
            );

        const metaLength =
            Number(
                page.meta_length || 0
            );

        const responseTime =
            Number(
                page.response_time_ms || 0
            );

        return [
            {
                label: "HTTPS",

                value:
                    isTruthy(
                        page.is_https
                    ),

                detail:
                    isTruthy(
                        page.is_https
                    )
                        ? "Connexion sécurisée"
                        : "Le site n'utilise pas HTTPS",
            },

            {
                label: "Title",

                value:
                    Boolean(page.title) &&
                    titleLength >= 30 &&
                    titleLength <= 65,

                detail:
                    page.title
                        ? `${titleLength} caractères`
                        : "Title absent",
            },

            {
                label: "Meta description",

                value:
                    Boolean(
                        page.meta_description
                    ) &&
                    metaLength >= 70 &&
                    metaLength <= 170,

                detail:
                    page.meta_description
                        ? `${metaLength} caractères`
                        : "Meta description absente",
            },

            {
                label: "Canonical",

                value:
                    Boolean(
                        page.canonical_url
                    ),

                detail:
                    page.canonical_url
                        ? "Canonical détectée"
                        : "Canonical absente",
            },

            {
                label: "Robots",

                value:
                    Boolean(
                        page.meta_robots
                    ),

                detail:
                    page.meta_robots
                        ? page.meta_robots
                        : "Directive robots non trouvée",
            },

            {
                label: "Langue",

                value:
                    Boolean(
                        page.lang_attribute
                    ),

                detail:
                    page.lang_attribute
                        ? `Langue : ${page.lang_attribute}`
                        : "Attribut lang absent",
            },

            {
                label: "H1",

                value:
                    Number(
                        page.h1_count || 0
                    ) === 1 &&
                    page.h1_is_unique !== false,

                detail:
                    Number(
                        page.h1_count || 0
                    ) === 1
                        ? "Un H1 unique"
                        : `${page.h1_count || 0} H1 détecté(s)`,
            },

            {
                label: "Viewport",

                value:
                    isTruthy(
                        page.viewport_meta
                    ),

                detail:
                    isTruthy(
                        page.viewport_meta
                    )
                        ? "Viewport mobile détecté"
                        : "Viewport absent",
            },

            {
                label: "Structured Data",

                value:
                    isTruthy(
                        page.has_structured_data
                    ),

                detail:
                    isTruthy(
                        page.has_structured_data
                    )
                        ? "Données structurées détectées"
                        : "Aucune donnée structurée",
            },

            {
                label: "Contenu",

                value:
                    Number(
                        page.word_count || 0
                    ) >= 300,

                detail:
                    `${page.word_count || 0} mots`,
            },

            {
                label: "Images ALT",

                value:
                    Number(
                        page.images_without_alt_count ||
                        0
                    ) === 0,

                detail:
                    Number(
                        page.images_without_alt_count ||
                        0
                    ) === 0
                        ? "Toutes les images ont un ALT"
                        : `${page.images_without_alt} image(s) sans ALT`,
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

            {/* =========================================================
                HERO
            ========================================================= */}

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
                        Obtenez un rapport détaillé sur
                        la structure, le contenu et les
                        éléments techniques de votre page.
                    </p>

                </div>

            </section>

            {/* =========================================================
                FORM
            ========================================================= */}

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
                            Entrez l’URL du site que
                            vous souhaitez analyser.
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
                                    setUrl(
                                        e.target.value
                                    )
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
                                Title, meta description,
                                canonical, robots, HTTPS
                                et viewport.
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
                                Word count, H1, headings,
                                liens internes et externes.
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

            {/* =========================================================
                REPORT
            ========================================================= */}

            {report && (
                <section
                    className="analyse-report"
                    id="analyse-report"
                >

                    {/* =================================================
                        HEADER
                    ================================================= */}

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
                                {formatDate(
                                    report.createdAt
                                )}
                            </div>

                        </div>

                        <div className="report-actions">

                            <button
                                type="button"
                                className="report-secondary-btn"
                                onClick={
                                    handleNewAnalysis
                                }
                            >
                                + Nouvelle analyse
                            </button>

                            <button
                                type="button"
                                className="report-primary-btn"
                                onClick={
                                    handleDownloadPdf
                                }
                                disabled={
                                    downloadingPdf
                                }
                            >

                                {downloadingPdf
                                    ? "Génération du PDF..."
                                    : "↓ Télécharger le rapport"}

                            </button>

                        </div>

                    </div>

                    {/* DOWNLOAD ERROR */}

                    {downloadError && (
                        <div
                            className="analyse-error"
                            style={{
                                marginTop: 16,
                            }}
                        >

                            <div className="error-icon">
                                !
                            </div>

                            <div>

                                <strong>
                                    Téléchargement impossible
                                </strong>

                                <p>
                                    {downloadError}
                                </p>

                            </div>

                        </div>
                    )}

                    {/* =================================================
                        SCORE
                    ================================================= */}

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
                                    Votre page a obtenu
                                    un score de{" "}
                                    <strong>
                                        {report.score}/100
                                    </strong>{" "}
                                    sur les critères
                                    analysés.
                                </p>

                            </div>

                        </div>

                    </div>

                    {/* =================================================
                        QUICK METRICS
                    ================================================= */}

                    <div className="report-metrics">

                        <div className="metric-card">

                            <span className="metric-label">
                                TITLE
                            </span>

                            <strong>
                                {
                                    report.page
                                        .title_length || 0
                                }
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
                                {
                                    report.page
                                        .response_time_ms || 0
                                }
                            </strong>

                            <small>
                                ms
                            </small>

                        </div>

                    </div>

                    {/* =================================================
                        SEO CHECKS
                    ================================================= */}

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
                                Les éléments techniques
                                essentiels de votre page.
                            </p>

                        </div>

                        <div className="checks-grid">

                            {checks.map(
                                (
                                    check,
                                    index
                                ) => (
                                    <div
                                        className={`check-card ${
                                            check.value
                                                ? "check-pass"
                                                : "check-fail"
                                        }`}
                                        key={`${check.label}-${index}`}
                                    >

                                        <div className="check-status">
                                            {check.value
                                                ? "✓"
                                                : "!"}
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
                                )
                            )}

                        </div>

                    </section>

                    {/* =================================================
                        CONTENT
                    ================================================= */}

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
                                Analyse de la structure
                                éditoriale et des éléments HTML.
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
                                    {
                                        stats.internalLinks +
                                        stats.externalLinks
                                    }
                                </strong>

                                <small>
                                    liens détectés
                                </small>

                            </div>

                        </div>

                        {/* HEADINGS */}

                        {report.headings.length >
                            0 && (
                            <div className="detail-box">

                                <div className="detail-box-header">

                                    <h4>
                                        Structure des headings
                                    </h4>

                                    <span>
                                        {
                                            report.headings
                                                .length
                                        }
                                    </span>

                                </div>

                                <div className="headings-list">

                                    {report.headings
                                        .slice(
                                            0,
                                            30
                                        )
                                        .map(
                                            (
                                                heading,
                                                index
                                            ) => (
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
                                                        #
                                                        {heading.position ||
                                                            index +
                                                                1}
                                                    </span>

                                                </div>
                                            )
                                        )}

                                </div>

                            </div>
                        )}

                    </section>

                    {/* =================================================
                        IMAGES
                    ================================================= */}

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
                                Vérification des images
                                et de leurs attributs ALT.
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

                        {report.images.length >
                            0 && (
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
                                    .slice(
                                        0,
                                        25
                                    )
                                    .map(
                                        (
                                            image,
                                            index
                                        ) => (
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
                                        )
                                    )}

                            </div>
                        )}

                    </section>

                    {/* =================================================
                        KEYWORDS
                    ================================================= */}

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
                                Les mots-clés les plus
                                présents dans le contenu analysé.
                            </p>

                        </div>

                        {report.keywords.length >
                        0 ? (
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
                                    .slice(
                                        0,
                                        30
                                    )
                                    .map(
                                        (
                                            keyword,
                                            index
                                        ) => (
                                            <div
                                                className="keyword-row"
                                                key={`${keyword.keyword}-${index}`}
                                            >

                                                <strong>
                                                    {keyword.keyword ||
                                                        "—"}
                                                </strong>

                                                <span>
                                                    {
                                                        keyword.occurrences ??
                                                        "—"
                                                    }
                                                </span>

                                                <span className="density-value">

                                                    {
                                                        keyword.density_percent ??
                                                        "—"
                                                    }

                                                    {typeof keyword.density_percent ===
                                                    "number"
                                                        ? "%"
                                                        : ""}

                                                </span>

                                            </div>
                                        )
                                    )}

                            </div>
                        ) : (
                            <div className="empty-report">

                                <span>
                                    ◌
                                </span>

                                <p>
                                    Aucun mot-clé
                                    exploitable n’a
                                    été détecté.
                                </p>

                            </div>
                        )}

                    </section>

                    {/* =================================================
                        FOOTER
                    ================================================= */}

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