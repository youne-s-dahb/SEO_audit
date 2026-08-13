import {
    useEffect,
    useMemo,
    useState,
} from "react";

import { Link } from "react-router-dom";

import { useAuth } from "../components/AuthContext";

import AuditResult from "../components/AuditResult";

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
            return new URL(report.url).hostname.replace(/^www\./i, "");
        } catch {
            return String(report.url)
                .replace(/^https?:\/\//i, "")
                .replace(/^www\./i, "")
                .split("/")[0] || "Site";
        }
    }

    return "Site";
};

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

    useEffect(() => {
        loadRecent();
    }, [user?.id]);

    async function loadRecent() {
        setIsLoadingRecent(true);

        try {
            const result = await getAuditHistory();

            if (!result.ok) {
                setError(result.error || "Erreur lors du chargement des audits.");
                setRecentReports([]);
                return;
            }

            const reports = Array.isArray(result.reports) ? result.reports : [];
            const userReports = reports.filter((report) => {
                if (!user) {
                    return false;
                }

                if (report?.requestedBy && report.requestedBy?.id && user?.id) {
                    return Number(report.requestedBy.id) === Number(user.id);
                }

                if (report?.userId && user?.id) {
                    return Number(report.userId) === Number(user.id);
                }

                return true;
            });

            setRecentReports(
                userReports
                    .filter(isCompletedAudit)
                    .slice(0, 3)
            );
            setError("");
        } catch (err) {
            console.error("HOME AUDIT ERROR:", err);
            setError("Erreur lors du chargement des audits.");
            setRecentReports([]);
        } finally {
            setIsLoadingRecent(false);
        }
    }

    async function handleAudit(e) {
        e.preventDefault();

        setError("");
        setAuditResult(null);

        const cleanUrl = url.trim();

        if (!cleanUrl) {
            setError("3afak dkhel URL dyal site.");
            return;
        }

        setIsRunning(true);

        try {
            const result = await runAudit(cleanUrl);

            if (!result.ok) {
                setError(result.error);
                return;
            }

            setAuditResult(result.audit);
            setUrl("");
            await loadRecent();
        } catch (err) {
            console.error("HOME AUDIT ERROR:", err);
            setError("Chi mochkil wa9e3 f l'audit.");
        } finally {
            setIsRunning(false);
        }
    }

    function getScoreClass(score, scoreColor) {
        const color = String(scoreColor || "").toLowerCase();

        if (color === "green") {
            return "score-good";
        }

        if (color === "orange" || color === "yellow") {
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

    const completedCount = useMemo(
        () => recentReports.length,
        [recentReports]
    );

    function displayUrl(urlValue) {
        if (!urlValue) {
            return "URL inconnue";
        }

        return String(urlValue).replace(/^https?:\/\//i, "").replace(/\/$/, "");
    }

    return (
        <div className="dashboard">
            <div className="dashboard-header">
                <span className="eyebrow">Dashboard</span>

                <h1>
                    Marhba, {user?.name || user?.email || "User"} 👋
                </h1>

                <p className="dashboard-subtitle">
                    Analysez votre site et obtenez un rapport SEO clair et professionnel.
                </p>
            </div>

            <div className="audit-launch-card">
                <div className="audit-launch-content">
                    <div className="audit-launch-title">
                        <div>
                            <h2>Audit SEO</h2>
                            <p>Entrez l'URL de votre site pour commencer l'analyse.</p>
                        </div>
                    </div>

                    <form className="audit-launch-form" onSubmit={handleAudit}>
                        <input
                            type="url"
                            placeholder="https://exemple.com"
                            value={url}
                            onChange={(e) => setUrl(e.target.value)}
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
                                    <span className="spinner" />
                                    Analyse...
                                </>
                            ) : (
                                <>
                                    Lancer l'audit
                                    <span>→</span>
                                </>
                            )}
                        </button>
                    </form>

                    {error && <p className="form-error">{error}</p>}
                </div>
            </div>

            {auditResult && <AuditResult audit={auditResult} />}

            <div className="dashboard-section">
                <div className="dashboard-section-header">
                    <div>
                        <span className="section-kicker">VOS RAPPORTS</span>
                        <h2>Derniers audits</h2>
                        <p>Les 3 derniers audits terminés</p>
                    </div>

                    <Link to="/history" className="link-small">
                        Voir tout →
                    </Link>
                </div>

                {isLoadingRecent ? (
                    <div className="recent-loading">
                        <div className="spinner" />
                        <span>Chargement...</span>
                    </div>
                ) : recentReports.length === 0 ? (
                    <div className="empty-state">
                        <p>Aucun audit terminé pour le moment.</p>
                        <Link to="/" className="btn btn-primary">
                            Lancer votre premier audit
                        </Link>
                    </div>
                ) : (
                    <div className="home-report-list">
                        {recentReports.map((report) => {
                            const score = getReportScore(report);
                            const scoreClass = getScoreClass(score, report.scoreColor);

                            return (
                                <div className="home-report-card" key={report.id ?? report.url ?? `audit-${Math.random()}`}>
                                    <div className="home-report-left">
                                        <div className="site-favicon">🌐</div>

                                        <div className="home-report-info">
                                            <strong title={report.url || ""}>
                                                {getReportSiteName(report)}
                                            </strong>
                                            <span>{displayUrl(report.url)}</span>
                                        </div>
                                    </div>

                                    <div className="home-report-meta">
                                        <span className="history-status completed">
                                            <span className="status-dot" />
                                            Completed
                                        </span>

                                        <div className="home-score">
                                            <strong className={scoreClass}>{score}</strong>
                                            <small>/100</small>
                                        </div>

                                        <Link to={`/audits/${report.id}`} className="home-report-button">
                                            Rapport <span>→</span>
                                        </Link>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}

                {completedCount > 0 && (
                    <div className="reports-footer">
                        <Link to="/history" className="reports-footer-link">
                            Consulter tous les rapports <span>→</span>
                        </Link>
                    </div>
                )}
            </div>
        </div>
    );
}