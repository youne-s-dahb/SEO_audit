import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { useAuth } from "../components/AuthContext";
import AuditResult from "../components/AuditResult";

export default function Home() {
    const {
        user,
        runAudit,
        getAuditHistory,
    } = useAuth();
    const [auditResult, setAuditResult] = useState(null);
    const [url, setUrl] = useState("");
    const [isRunning, setIsRunning] = useState(false);
    const [error, setError] = useState("");
    const [recentReports, setRecentReports] = useState([]);
    const [isLoadingRecent, setIsLoadingRecent] = useState(true);

    // ==========================================
    // Load recent audits
    // ==========================================
    useEffect(() => {
        loadRecent();
    }, []);

    async function loadRecent() {
        setIsLoadingRecent(true);

        const result = await getAuditHistory();

        console.log("RECENT AUDITS:", result);

        if (result.ok) {
            setRecentReports(
                result.reports.slice(0, 5)
            );
        } else {
            setError(result.error);
        }

        setIsLoadingRecent(false);
    }

    // ==========================================
    // Run audit
    // ==========================================
    async function handleAudit(e) {

    e.preventDefault();

    setError("");
    setAuditResult(null);

    if (!url.trim()) {
        setError(
            "3afak dkhel URL dyal site."
        );
        return;
    }

    setIsRunning(true);

    try {

        const result =
            await runAudit(url.trim());

        console.log(
            "AUDIT FRONT RESULT:",
            result
        );

        if (!result.ok) {
            setError(result.error);
            return;
        }

        /*
         * Afficher résultat مباشرة
         */
        setAuditResult(result.audit);

        /*
         * Nettoyer input
         */
        setUrl("");

        /*
         * Update historique
         */
        await loadRecent();

    } finally {

        setIsRunning(false);

    }
}

    return (
        <div className="dashboard">

            {/* ================================
                HEADER
            ================================= */}

            <div className="dashboard-header">

                <span className="eyebrow">
                    Dashboard
                </span>

                <h1>
                    Marhba,{" "}
                    {user?.name || user?.email} 👋
                </h1>

                <p className="dashboard-subtitle">
                    Dkhel URL dyal site bach tbda
                    audit SEO jdid.
                </p>

            </div>


            {/* ================================
                AUDIT FORM
            ================================= */}

            <div className="audit-launch-card">
                
                <form
                    className="audit-launch-form"
                    onSubmit={handleAudit}
                >

                    <input
                        type="url"
                        placeholder="https://exemple.com"
                        value={url}
                        onChange={(e) =>
                            setUrl(e.target.value)
                        }
                        disabled={isRunning}
                        className="audit-url-input"
                    />

                    <button
                        type="submit"
                        className="btn btn-primary"
                        disabled={isRunning}
                        aria-busy={isRunning}
                    >

                        {isRunning ? (
                            <>
                                <span
                                    className="spinner"
                                    aria-hidden="true"
                                />

                                Kaydir audit...
                            </>
                        ) : (
                            "Bda Audit"
                        )}

                    </button>

                </form>


                {error && (
                    <p className="form-error">
                        {error}
                    </p>
                )}

            </div>
                {auditResult && (
                    <AuditResult
                        audit={auditResult}
                    />
                )}

            {/* ================================
                RECENT AUDITS
            ================================= */}

            <div className="dashboard-section">

                <div className="dashboard-section-header">

                    <h2>
                        Akhir Audits
                    </h2>

                    <Link
                        to="/history"
                        className="link-small"
                    >
                        Chouf koulchi →
                    </Link>

                </div>


                {/* Loading */}

                {isLoadingRecent ? (

                    <p className="page-loading">
                        Kaytloaded...
                    </p>

                ) : recentReports.length === 0 ? (

                    /* Empty */

                    <div className="empty-state">

                        <p>
                            Mazal madertich chi audit.
                            Bda b'URL fo9.
                        </p>

                    </div>

                ) : (

                    /* Reports */

                    <div className="report-list">

                        {recentReports.map(
                            (report) => (

                                <Link
                                    to={`/audits/${report.id}`}
                                    className="report-row"
                                    key={report.id}
                                >

                                    <div className="report-row-main">

                                        {/* URL */}

                                        <span className="report-url">

                                            {report.url ||
                                                report.site ||
                                                "Site"}

                                        </span>


                                        {/* Date */}

                                        <span className="report-date">

                                            {report.createdAt
                                                ? new Date(
                                                      report.createdAt
                                                  ).toLocaleDateString()
                                                : ""}

                                        </span>

                                    </div>


                                    {/* SCORE */}

                                    {typeof report.score ===
                                        "number" && (

                                        <span
                                            className={`report-score ${
                                                report.score >= 80
                                                    ? "score-good"
                                                    : report.score >=
                                                      50
                                                    ? "score-mid"
                                                    : "score-bad"
                                            }`}
                                        >
                                            {report.score}
                                        </span>

                                    )}

                                </Link>

                            )
                        )}

                    </div>

                )}

            </div>

        </div>
    );
}

