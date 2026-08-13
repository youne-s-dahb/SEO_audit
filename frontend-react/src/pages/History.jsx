import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { useAuth } from "../components/AuthContext";

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

export default function History() {
    const { user, getAuditHistory } = useAuth();
    const [reports, setReports] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState("");
    const [search, setSearch] = useState("");

    useEffect(() => {
        load();
    }, [user?.id]);

    async function load() {
        setIsLoading(true);
        setError("");

        try {
            const result = await getAuditHistory();

            if (!result.ok) {
                setError(result.error || "Ma9drnach njibo l'historique.");
                setReports([]);
                return;
            }

            const userReports = Array.isArray(result.reports) ? result.reports : [];
            const filteredReports = userReports.filter((report) => {
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

            setReports(filteredReports);
        } catch (err) {
            console.error("HISTORY ERROR:", err);
            setError("Ma9drnach njibo l'historique.");
            setReports([]);
        } finally {
            setIsLoading(false);
        }
    }

    const completedReports = reports.filter(isCompletedAudit);
    const filtered = completedReports.filter((report) => {
        const siteName = getReportSiteName(report);
        const query = search.toLowerCase().trim();

        return (
            siteName.toLowerCase().includes(query) ||
            String(report?.url || "").toLowerCase().includes(query)
        );
    });

    return (
        <div className="dashboard">
            <div className="dashboard-header">
                <span className="eyebrow">Historique</span>
                <h1>Rapports d'audits</h1>
                <p className="dashboard-subtitle">Ga3 les audits SEO li complets dyalek.</p>
            </div>

            <div className="history-toolbar">
                <div className="history-search-wrapper">
                    <span className="history-search-icon">⌕</span>
                    <input
                        type="text"
                        placeholder="Chercher un site..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="history-search"
                    />
                </div>

                <span className="history-count">
                    {filtered.length} rapport{filtered.length !== 1 ? "s" : ""}
                </span>
            </div>

            {isLoading && (
                <div className="history-loading-card">
                    <div className="spinner" />
                    <span>Kaytloaded...</span>
                </div>
            )}

            {!isLoading && error && (
                <div className="history-error-card">
                    <div className="history-error-icon">!</div>
                    <div>
                        <strong>Erreur</strong>
                        <p>{error}</p>
                    </div>
                </div>
            )}

            {!isLoading && !error && filtered.length === 0 && (
                <div className="history-empty-card">
                    <div className="history-empty-icon">✓</div>
                    <h3>Aucun rapport</h3>
                    <p>Mazal ma kaynach chi audit completed.</p>
                    <Link to="/" className="history-report-button">
                        + Bda audit jdid
                    </Link>
                </div>
            )}

            {!isLoading && !error && filtered.length > 0 && (
                <div className="history-reports">
                    <div className="history-table-header">
                        <span>Site</span>
                        <span>Date</span>
                        <span>Statut</span>
                        <span>Score</span>
                        <span>Rapport</span>
                    </div>

                    {filtered.map((report) => {
                        const siteName = getReportSiteName(report);
                        const score = getReportScore(report);
                        const statusLabel = isCompletedAudit(report) ? "Completed" : "Failed";
                        const statusClassName = isCompletedAudit(report) ? "completed" : "failed";

                        return (
                            <div className="history-report-card" key={report.id ?? report.url ?? `audit-${Math.random()}`}>
                                <div className="history-site">
                                    <div className="site-favicon">S</div>
                                    <div>
                                        <strong>{siteName}</strong>
                                        <span>{report.url || "URL inconnue"}</span>
                                    </div>
                                </div>

                                <span className="history-date">
                                    {report.createdAt ? new Date(report.createdAt).toLocaleDateString("fr-FR") : "—"}
                                </span>

                                <span className={`history-status ${statusClassName}`}>
                                    <span className="status-dot" />
                                    {statusLabel}
                                </span>

                                <span
                                    className={`history-score ${
                                        score >= 80 ? "score-good" : score >= 50 ? "score-mid" : "score-bad"
                                    }`}
                                >
                                    {Number.isFinite(score) ? Math.round(score) : "—"}
                                    {Number.isFinite(score) && <small>/100</small>}
                                </span>

                                <Link to={`/audits/${report.id}`} className="history-report-button">
                                    Voir le rapport →
                                </Link>
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}