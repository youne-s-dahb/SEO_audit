import {
    useEffect,
    useMemo,
    useState,
} from "react";

import {
    Link,
    useNavigate,
} from "react-router-dom";

import { useAuth } from "../components/AuthContext";

import AuditResult from "../components/AuditResult";
import "../style/Home.css"

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

const isCompletedAudit = (report) => {
    const status = String(report?.status || "")
        .toLowerCase()
        .replace(/[\s_-]/g, "");

    return [
        "completed",
        "complete",
        "done",
        "finished",
        "termine",
        "terminé",
    ].includes(status);
};


const getReportScore = (report) => {
    const value =
        report?.score ??
        report?.globalScore ??
        report?.global_score ??
        0;

    return Number(value) || 0;
};


const getReportSiteName = (report) => {

    if (report?.siteName) {
        return report.siteName;
    }

    if (report?.site_name) {
        return report.site_name;
    }

    if (report?.url) {

        try {

            return new URL(report.url)
                .hostname
                .replace(/^www\./i, "");

        } catch {

            return String(report.url)
                .replace(/^https?:\/\//i, "")
                .replace(/^www\./i, "")
                .split("/")[0] || "Site";
        }
    }

    return "Site";
};


function getScoreClass(
    score,
    scoreColor
) {

    const color =
        String(scoreColor || "")
            .toLowerCase();

    if (color === "green") {
        return "score-good";
    }

    if (
        color === "orange" ||
        color === "yellow"
    ) {
        return "score-mid";
    }

    if (color === "red") {
        return "score-bad";
    }

    if (score >= 80) {
        return "score-good";
    }

    if (score >= 50) {
        return "score-mid";
    }

    return "score-bad";
}


function displayUrl(urlValue) {

    if (!urlValue) {
        return "URL inconnue";
    }

    return String(urlValue)
        .replace(/^https?:\/\//i, "")
        .replace(/\/$/, "");
}


/*
|--------------------------------------------------------------------------
| AUDIT ANALYSIS STEPS
|--------------------------------------------------------------------------
*/

const AUDIT_STEPS = [

    {
        id: "connection",
        label: "Connexion au site",
        description:
            "Connexion sécurisée au site web...",
        icon: "◎",
        start: 0,
        end: 18,
    },

    {
        id: "structure",
        label: "Analyse de la structure",
        description:
            "Analyse des pages et de la structure HTML...",
        icon: "⌘",
        start: 18,
        end: 38,
    },

    {
        id: "performance",
        label: "Analyse des performances",
        description:
            "Mesure des performances et du temps de chargement...",
        icon: "⚡",
        start: 38,
        end: 62,
    },

    {
        id: "seo",
        label: "Vérification SEO",
        description:
            "Vérification des éléments SEO techniques...",
        icon: "◈",
        start: 62,
        end: 78,
    },

    {
        id: "google-maps",
        label: "Analyse Google Maps",
        description:
            "Vérification de la présence locale sur Google Maps...",
        icon: "📍",
        start: 78,
        end: 88,
    },

    {
        id: "report",
        label: "Génération du rapport",
        description:
            "Préparation de votre rapport professionnel...",
        icon: "▤",
        start: 88,
        end: 96,
    },

];


export default function Home() {

    const {
        user,
        runAudit,
        getAuditHistory,
    } = useAuth();


    const navigate = useNavigate();


    /*
    |--------------------------------------------------------------------------
    | MAIN STATE
    |--------------------------------------------------------------------------
    */

    const [
        auditResult,
        setAuditResult,
    ] = useState(null);


    const [
        url,
        setUrl,
    ] = useState("");


    const [
        isRunning,
        setIsRunning,
    ] = useState(false);


    const [
        error,
        setError,
    ] = useState("");


    /*
    |--------------------------------------------------------------------------
    | RECENT AUDITS
    |--------------------------------------------------------------------------
    */

    const [
        recentReports,
        setRecentReports,
    ] = useState([]);


    const [
        isLoadingRecent,
        setIsLoadingRecent,
    ] = useState(true);


    const [
        recentLoadingProgress,
        setRecentLoadingProgress,
    ] = useState(0);


    /*
    |--------------------------------------------------------------------------
    | REPORT OPENING
    |--------------------------------------------------------------------------
    */

    const [
        openingReportId,
        setOpeningReportId,
    ] = useState(null);


    /*
    |--------------------------------------------------------------------------
    | ANALYSIS STATE
    |--------------------------------------------------------------------------
    */

    const [
        analysisStep,
        setAnalysisStep,
    ] = useState(0);


    const [
        analysisProgress,
        setAnalysisProgress,
    ] = useState(0);


    /*
    |--------------------------------------------------------------------------
    | LOAD RECENT AUDITS
    |--------------------------------------------------------------------------
    */

    useEffect(() => {

        loadRecent();

    }, [user?.id]);


    async function loadRecent() {

        setIsLoadingRecent(true);

        setRecentLoadingProgress(0);

        let progress = 0;


        /*
         * Progress animation:
         * 0 → 90%
         *
         * 100% only when backend
         * actually returns.
         */

        const progressInterval =
            setInterval(() => {

                progress +=
                    Math.random() * 8;


                if (progress >= 90) {

                    progress = 90;

                    clearInterval(
                        progressInterval
                    );
                }


                setRecentLoadingProgress(
                    Math.floor(progress)
                );

            }, 180);


        try {

            const result =
                await getAuditHistory();


            if (!result.ok) {

                clearInterval(
                    progressInterval
                );


                setError(
                    result.error ||
                    "Erreur lors du chargement des audits."
                );


                setRecentReports([]);

                return;
            }


            const reports =
                Array.isArray(
                    result.reports
                )
                    ? result.reports
                    : [];


            /*
             * Only current user's reports.
             */

            const userReports =
                reports.filter(
                    (report) => {

                        if (!user) {
                            return false;
                        }


                        if (
                            report?.requestedBy &&
                            report.requestedBy?.id &&
                            user?.id
                        ) {

                            return (
                                Number(
                                    report.requestedBy.id
                                ) ===
                                Number(user.id)
                            );
                        }


                        if (
                            report?.userId &&
                            user?.id
                        ) {

                            return (
                                Number(
                                    report.userId
                                ) ===
                                Number(user.id)
                            );
                        }


                        return true;

                    }
                );


            /*
             * Only completed audits.
             *
             * Latest 3.
             */

            const completedReports =
                userReports
                    .filter(
                        isCompletedAudit
                    )
                    .slice(0, 3);


            setRecentReports(
                completedReports
            );


            setError("");


            /*
             * Backend finished.
             */

            clearInterval(
                progressInterval
            );


            setRecentLoadingProgress(
                100
            );


            /*
             * Keep 100% visible
             * for 500ms.
             */

            await new Promise(
                (resolve) =>
                    setTimeout(
                        resolve,
                        500
                    )
            );


        } catch (err) {

            console.error(
                "HOME AUDIT ERROR:",
                err
            );


            clearInterval(
                progressInterval
            );


            setError(
                "Erreur lors du chargement des audits."
            );


            setRecentReports([]);

        } finally {

            clearInterval(
                progressInterval
            );


            setIsLoadingRecent(
                false
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | OPEN REPORT
    |--------------------------------------------------------------------------
    */

    function handleOpenReport(
        reportId
    ) {

        if (!reportId) {
            return;
        }


        setOpeningReportId(
            reportId
        );


        /*
         * Small UX animation before
         * opening the report.
         */

        setTimeout(() => {

            navigate(
                `/audits/${reportId}`
            );

        }, 650);
    }


    /*
    |--------------------------------------------------------------------------
    | ANALYSIS PROGRESS
    |--------------------------------------------------------------------------
    */

    useEffect(() => {

        if (!isRunning) {
            return;
        }


        const interval =
            setInterval(() => {

                setAnalysisProgress(
                    (current) => {

                        const currentStep =
                            AUDIT_STEPS[
                                analysisStep
                            ];


                        if (!currentStep) {
                            return current;
                        }


                        const target =
                            currentStep.end;


                        if (
                            current <
                            target - 1
                        ) {

                            return Math.min(
                                current + 1,
                                target - 1
                            );
                        }


                        return current;

                    }
                );

            }, 120);


        return () => {

            clearInterval(
                interval
            );

        };

    }, [
        isRunning,
        analysisStep,
    ]);


    /*
    |--------------------------------------------------------------------------
    | CHANGE ANALYSIS STEP
    |--------------------------------------------------------------------------
    */

    useEffect(() => {

        if (!isRunning) {
            return;
        }


        const step =
            AUDIT_STEPS[
                analysisStep
            ];


        if (!step) {
            return;
        }


        const duration =
            analysisStep === 0
                ? 2000
                : analysisStep === 1
                ? 5000
                : analysisStep === 2
                ? 7000
                : analysisStep === 3
                ? 9000
                : analysisStep === 4
                ? 10050
                : 14000;


        const timer =
            setTimeout(() => {

                setAnalysisStep(
                    (current) =>
                        Math.min(
                            current + 1,
                            AUDIT_STEPS.length - 1
                        )
                );

            }, duration);


        return () => {

            clearTimeout(
                timer
            );

        };

    }, [
        isRunning,
        analysisStep,
    ]);


    /*
    |--------------------------------------------------------------------------
    | RUN AUDIT
    |--------------------------------------------------------------------------
    */

    async function handleAudit(e) {

        e.preventDefault();


        setError("");

        setAuditResult(null);


        const cleanUrl =
            url.trim();


        if (!cleanUrl) {

            setError(
                "3afak dkhel URL dyal site."
            );

            return;
        }


        /*
         * Reset analysis.
         */

        setAnalysisStep(0);

        setAnalysisProgress(5);

        setIsRunning(true);


        try {

            const result =
                await runAudit(
                    cleanUrl
                );


            /*
             * ERROR
             */

            if (!result.ok) {

                setError(
                    result.error ||
                    "Ma9dertch ndir l'audit."
                );


                setIsRunning(
                    false
                );


                setAnalysisProgress(
                    0
                );


                setAnalysisStep(
                    0
                );


                return;
            }


            /*
             * Backend returned successfully.
             */

            setAnalysisProgress(
                100
            );


            setAnalysisStep(
                AUDIT_STEPS.length - 1
            );


            /*
             * Show 100%.
             */

            await new Promise(
                (resolve) =>
                    setTimeout(
                        resolve,
                        650
                    )
            );


            setAuditResult(
                result.audit
            );


            setUrl("");


            setIsRunning(
                false
            );


            /*
             * Refresh recent reports.
             */

            await loadRecent();


        } catch (err) {

            console.error(
                "HOME AUDIT ERROR:",
                err
            );


            setError(
                "Chi mochkil wa9e3 f l'audit."
            );


            setIsRunning(
                false
            );


            setAnalysisProgress(
                0
            );


            setAnalysisStep(
                0
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | COMPLETED COUNT
    |--------------------------------------------------------------------------
    */

    const completedCount =
        useMemo(
            () =>
                recentReports.length,
            [recentReports]
        );


    /*
    |--------------------------------------------------------------------------
    | CURRENT STEP
    |--------------------------------------------------------------------------
    */

    const currentStep =
        AUDIT_STEPS[
            Math.min(
                analysisStep,
                AUDIT_STEPS.length - 1
            )
        ];


    /*
    |--------------------------------------------------------------------------
    | UI
    |--------------------------------------------------------------------------
    */

    return (

        <div className="dashboard">


            {/* =====================================================
                HEADER
            ====================================================== */}

            <div className="dashboard-header">

                <span className="eyebrow">
                    Dashboard
                </span>


                <h1>

                    Marhba,{" "}

                    {user?.name ||
                        user?.email ||
                        "User"}

                    {" "}👋

                </h1>


                <p className="dashboard-subtitle">

                    Analysez votre site et obtenez
                    un rapport SEO clair et
                    professionnel.

                </p>

            </div>


            {/* =====================================================
                AUDIT LAUNCH
            ====================================================== */}

            {!isRunning ? (

                <div className="audit-launch-card">

                    <div className="audit-launch-content">

                        <div className="audit-launch-title">

                            <div>

                                <h2>
                                    Audit SEO
                                </h2>

                                <p>

                                    Entrez l'URL de
                                    votre site pour
                                    commencer
                                    l'analyse.

                                </p>

                            </div>

                        </div>


                        <form
                            className="audit-launch-form"
                            onSubmit={
                                handleAudit
                            }
                        >

                            <input
                                type="url"
                                placeholder="https://exemple.com"
                                value={url}
                                onChange={(e) =>
                                    setUrl(
                                        e.target.value
                                    )
                                }
                                className="audit-url-input"
                            />


                            <button
                                type="submit"
                                className="btn btn-primary"
                            >

                                Lancer l'audit

                                <span>
                                    →
                                </span>

                            </button>

                        </form>


                        {error && (

                            <p className="form-error">
                                {error}
                            </p>

                        )}

                    </div>

                </div>

            ) : (

                /* =================================================
                   ACTIVE ANALYSIS
                ================================================== */

                <div className="audit-analysis-card">

                    <div className="analysis-top">

                        <div className="analysis-live">

                            <span className="analysis-live-dot" />

                            ANALYSE EN COURS

                        </div>


                        <span className="analysis-percent">

                            {Math.min(
                                analysisProgress,
                                100
                            )}

                            %

                        </span>

                    </div>


                    <div className="analysis-site">

                        <div className="analysis-site-icon">
                            🌐
                        </div>


                        <div>

                            <strong>
                                {displayUrl(url)}
                            </strong>

                            <span>
                                Analyse SEO
                                complète
                            </span>

                        </div>

                    </div>


                    <div className="analysis-progress">

                        <div
                            className="analysis-progress-bar"
                            style={{
                                width:
                                    `${Math.min(
                                        analysisProgress,
                                        100
                                    )}%`,
                            }}
                        />

                    </div>


                    <div className="analysis-current">

                        <div className="analysis-current-icon">

                            <span>
                                {currentStep?.icon ||
                                    "◌"}
                            </span>

                        </div>


                        <div>

                            <strong>
                                {
                                    currentStep?.label
                                }
                            </strong>

                            <p>
                                {
                                    currentStep?.description
                                }
                            </p>

                        </div>


                        <div className="analysis-spinner">

                            <span />

                        </div>

                    </div>


                    <div className="analysis-steps">

                        {AUDIT_STEPS.map(
                            (
                                step,
                                index
                            ) => {

                                const isDone =
                                    index <
                                    analysisStep;


                                const isCurrent =
                                    index ===
                                    analysisStep;


                                return (

                                    <div
                                        className={`analysis-step ${
                                            isDone
                                                ? "done"
                                                : ""
                                        } ${
                                            isCurrent
                                                ? "current"
                                                : ""
                                        }`}
                                        key={
                                            step.id
                                        }
                                    >

                                        <div className="analysis-step-icon">

                                            {isDone
                                                ? "✓"
                                                : isCurrent
                                                ? "•"
                                                : index + 1}

                                        </div>


                                        <div>

                                            <strong>
                                                {
                                                    step.label
                                                }
                                            </strong>


                                            {isCurrent && (

                                                <span>
                                                    En cours...
                                                </span>

                                            )}


                                            {isDone && (

                                                <span>
                                                    Terminé
                                                </span>

                                            )}

                                        </div>

                                    </div>

                                );

                            }
                        )}

                    </div>


                    <div className="analysis-footer">

                        <span>
                            ⚡ Analyse automatique
                        </span>

                        <span>
                            Ne fermez pas cette page
                        </span>

                    </div>

                </div>

            )}


            {/* =====================================================
                RESULT
            ====================================================== */}

            {auditResult && (

                <AuditResult
                    audit={auditResult}
                />

            )}


            {/* =====================================================
                RECENT REPORTS
            ====================================================== */}

            <div className="dashboard-section">


                <div className="dashboard-section-header">

                    <div>

                        <span className="section-kicker">
                            VOS RAPPORTS
                        </span>

                        <h2>
                            Derniers audits
                        </h2>

                        <p>
                            Les 3 derniers audits
                            terminés
                        </p>

                    </div>


                    <Link
                        to="/history"
                        className="link-small"
                    >
                        Voir tout →
                    </Link>

                </div>


                {/* =================================================
                    RECENT LOADING
                ================================================== */}

                {isLoadingRecent ? (

                    <div className="home-recent-loading-card">

                        <div className="home-recent-loading-content">


                            {/* CIRCLE */}

                            <div
                                className="home-recent-progress-circle"
                                style={{
                                    "--progress":
                                        `${recentLoadingProgress * 3.6}deg`,
                                }}
                            >

                                <div className="home-recent-progress-inner">

                                    <strong>
                                        {
                                            recentLoadingProgress
                                        }%
                                    </strong>

                                    <span>
                                        Chargement
                                    </span>

                                </div>

                            </div>


                            {/* TEXT */}

                            <div className="home-recent-loading-text">

                                <h3>

                                    {
                                        recentLoadingProgress < 30
                                            ? "Connexion au serveur..."
                                            : recentLoadingProgress < 60
                                            ? "Récupération des audits..."
                                            : recentLoadingProgress < 90
                                            ? "Préparation des derniers rapports..."
                                            : recentLoadingProgress < 100
                                            ? "Presque terminé..."
                                            : "Audits chargés !"
                                    }

                                </h3>


                                <p>
                                    Nous récupérons
                                    vos derniers
                                    audits SEO.
                                </p>


                                <div className="home-recent-loading-dots">

                                    <span />
                                    <span />
                                    <span />

                                </div>

                            </div>

                        </div>

                    </div>

                ) : recentReports.length === 0 ? (

                    <div className="empty-state">

                        <p>
                            Aucun audit terminé
                            pour le moment.
                        </p>


                        <Link
                            to="/"
                            className="btn btn-primary"
                        >
                            Lancer votre
                            premier audit
                        </Link>

                    </div>

                ) : (

                    <div className="home-report-list">

                        {recentReports.map(
                            (report) => {

                                const score =
                                    getReportScore(
                                        report
                                    );


                                const scoreClass =
                                    getScoreClass(
                                        score,
                                        report.scoreColor
                                    );


                                const isOpening =
                                    openingReportId ===
                                    report.id;


                                return (

                                    <div
                                        className="home-report-card"
                                        key={
                                            report.id ??
                                            report.url
                                        }
                                    >


                                        {/* SITE */}

                                        <div className="home-report-left">

                                            <div className="site-favicon">
                                                🌐
                                            </div>


                                            <div className="home-report-info">

                                                <strong
                                                    title={
                                                        report.url ||
                                                        ""
                                                    }
                                                >
                                                    {
                                                        getReportSiteName(
                                                            report
                                                        )
                                                    }
                                                </strong>


                                                <span>
                                                    {
                                                        displayUrl(
                                                            report.url
                                                        )
                                                    }
                                                </span>

                                            </div>

                                        </div>


                                        {/* META */}

                                        <div className="home-report-meta">


                                            <span className="history-status completed">

                                                <span className="status-dot" />

                                                Completed

                                            </span>


                                            {/* SCORE */}

                                            <div className="home-score">

                                                <strong
                                                    className={
                                                        scoreClass
                                                    }
                                                >
                                                    {
                                                        score
                                                    }
                                                </strong>

                                                <small>
                                                    /100
                                                </small>

                                            </div>


                                            {/* REPORT BUTTON */}

                                            <button
                                                type="button"
                                                className={`home-report-button ${
                                                    isOpening
                                                        ? "opening"
                                                        : ""
                                                }`}
                                                onClick={() =>
                                                    handleOpenReport(
                                                        report.id
                                                    )
                                                }
                                                disabled={
                                                    isOpening
                                                }
                                            >

                                                {isOpening ? (

                                                    <>
                                                        <span className="report-button-spinner" />

                                                        <span>
                                                            Ouverture...
                                                        </span>
                                                    </>

                                                ) : (

                                                    <>
                                                        <span>
                                                            Rapport
                                                        </span>

                                                        <span>
                                                            →
                                                        </span>
                                                    </>

                                                )}

                                            </button>

                                        </div>

                                    </div>

                                );

                            }
                        )}

                    </div>

                )}


                {/* FOOTER */}

                {completedCount > 0 && (

                    <div className="reports-footer">

                        <Link
                            to="/history"
                            className="reports-footer-link"
                        >

                            Consulter tous
                            les rapports

                            {" "}

                            <span>
                                →
                            </span>

                        </Link>

                    </div>

                )}

            </div>

        </div>
    );
}

