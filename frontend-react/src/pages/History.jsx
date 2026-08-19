import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { useAuth } from "../components/AuthContext";
import "../style/History.css";
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
            return (
                String(report.url)
                    .replace(/^https?:\/\//i, "")
                    .replace(/^www\./i, "")
                    .split("/")[0] || "Site"
            );
        }
    }

    return "Site";
};

export default function History() {
    const { user, getAuditHistory } = useAuth();

    const [reports, setReports] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [loadingProgress, setLoadingProgress] = useState(0);
    const [error, setError] = useState("");
    const [search, setSearch] = useState("");

    useEffect(() => {
        load();
    }, [user?.id]);

    async function load() {
        setIsLoading(true);
        setError("");
        setLoadingProgress(0);

        let progress = 0;

        /*
         * Animation 0 → 90%
         *
         * On ne va jamais à 100% ici.
         * Le 100% arrive seulement quand le backend
         * a réellement répondu.
         */
        const progressInterval = setInterval(() => {
            progress += Math.random() * 8;

            if (progress >= 90) {
                progress = 90;
                clearInterval(progressInterval);
            }

            setLoadingProgress(Math.floor(progress));
        }, 180);

        try {
            const result = await getAuditHistory();

            if (!result.ok) {
                clearInterval(progressInterval);

                setError(
                    result.error ||
                        "Ma9drnach njibo l'historique."
                );

                setReports([]);

                return;
            }

            const userReports = Array.isArray(result.reports)
                ? result.reports
                : [];

            /*
             * On garde uniquement les audits
             * appartenant à l'utilisateur connecté.
             */
            const filteredReports = userReports.filter((report) => {
                if (!user) {
                    return false;
                }

                if (
                    report?.requestedBy?.id &&
                    user?.id
                ) {
                    return (
                        Number(report.requestedBy.id) ===
                        Number(user.id)
                    );
                }

                if (report?.userId && user?.id) {
                    return (
                        Number(report.userId) ===
                        Number(user.id)
                    );
                }

                return true;
            });

            setReports(filteredReports);

            /*
             * Backend terminé → 100%
             */
            clearInterval(progressInterval);

            setLoadingProgress(100);

            /*
             * On laisse 100% visible pendant 500ms
             * avant d'afficher les résultats.
             */
            await new Promise((resolve) =>
                setTimeout(resolve, 500)
            );
        } catch (err) {
            console.error("HISTORY ERROR:", err);

            clearInterval(progressInterval);

            setError(
                "Ma9drnach njibo l'historique."
            );

            setReports([]);
        } finally {
            clearInterval(progressInterval);
            setIsLoading(false);
        }
    }

    /*
     * Garde seulement les audits Completed
     */
    const completedReports = reports.filter(
        isCompletedAudit
    );

    /*
     * Recherche
     */
    const filtered = completedReports.filter(
        (report) => {
            const siteName =
                getReportSiteName(report);

            const query = search
                .toLowerCase()
                .trim();

            return (
                siteName
                    .toLowerCase()
                    .includes(query) ||
                String(report?.url || "")
                    .toLowerCase()
                    .includes(query)
            );
        }
    );

    return (
        <div className="dashboard">

            {/* =========================
                HEADER
            ========================= */}

            <div className="dashboard-header">
                <span className="eyebrow">
                    Historique
                </span>

                <h1>
                    Rapports d'audits
                </h1>

                <p className="dashboard-subtitle">
                    Ga3 les audits SEO li complets
                    dyalek.
                </p>
            </div>

            {/* =========================
                TOOLBAR
            ========================= */}

            <div className="history-toolbar">

                <div className="history-search-wrapper">
                    <span className="history-search-icon">
                        ⌕
                    </span>

                    <input
                        type="text"
                        placeholder="Chercher un site..."
                        value={search}
                        onChange={(e) =>
                            setSearch(e.target.value)
                        }
                        className="history-search"
                    />
                </div>

                <span className="history-count">
                    {filtered.length} rapport
                    {filtered.length !== 1
                        ? "s"
                        : ""}
                </span>
            </div>

            {/* =========================
                LOADING
            ========================= */}

            {isLoading && (
                <div className="history-loading-card">

                    <div className="history-loading-content">

                        {/* CIRCLE */}

                        <div
                            className="history-progress-circle"
                            style={{
                                "--progress": `${loadingProgress * 3.6}deg`,
                            }}
                        >
                            <div className="history-progress-inner">

                                <strong>
                                    {loadingProgress}%
                                </strong>

                                <span>
                                    Chargement
                                </span>

                            </div>
                        </div>

                        {/* TEXT */}

                        <div className="history-loading-text">

                            <h3>
                                {loadingProgress < 30
                                    ? "Connexion au serveur..."
                                    : loadingProgress < 60
                                    ? "Récupération des audits..."
                                    : loadingProgress < 90
                                    ? "Préparation de l'historique..."
                                    : loadingProgress < 100
                                    ? "Presque terminé..."
                                    : "Historique chargé !"}
                            </h3>

                            <p>
                                Nous récupérons vos
                                derniers audits SEO.
                            </p>

                            {/* DOTS */}

                            <div className="history-loading-dots">
                                <span />
                                <span />
                                <span />
                            </div>

                        </div>

                    </div>

                </div>
            )}

            {/* =========================
                ERROR
            ========================= */}

            {!isLoading && error && (
                <div className="history-error-card">

                    <div className="history-error-icon">
                        !
                    </div>

                    <div>
                        <strong>
                            Erreur
                        </strong>

                        <p>
                            {error}
                        </p>
                    </div>

                </div>
            )}

            {/* =========================
                EMPTY
            ========================= */}

            {!isLoading &&
                !error &&
                filtered.length === 0 && (
                    <div className="history-empty-card">

                        <div className="history-empty-icon">
                            ✓
                        </div>

                        <h3>
                            Aucun rapport
                        </h3>

                        <p>
                            Mazal ma kaynach chi
                            audit completed.
                        </p>

                        <Link
                            to="/"
                            className="history-report-button"
                        >
                            + Bda audit jdid
                        </Link>

                    </div>
                )}

            {/* =========================
                REPORTS
            ========================= */}

            {!isLoading &&
                !error &&
                filtered.length > 0 && (
                    <div className="history-reports">

                        {/* TABLE HEADER */}

                        <div className="history-table-header">

                            <span>
                                Site
                            </span>

                            <span>
                                Date
                            </span>

                            <span>
                                Statut
                            </span>

                            <span>
                                Score
                            </span>

                            <span>
                                Rapport
                            </span>

                        </div>

                        {/* REPORTS */}

                        {filtered.map((report) => {

                            const siteName =
                                getReportSiteName(
                                    report
                                );

                            const score =
                                getReportScore(
                                    report
                                );

                            const statusLabel =
                                isCompletedAudit(
                                    report
                                )
                                    ? "Completed"
                                    : "Failed";

                            const statusClassName =
                                isCompletedAudit(
                                    report
                                )
                                    ? "completed"
                                    : "failed";

                            return (
                                <div
                                    className="history-report-card"
                                    key={
                                        report.id ??
                                        report.url ??
                                        `audit-${Math.random()}`
                                    }
                                >

                                    {/* SITE */}

                                    <div className="history-site">

                                        <div className="site-favicon">
                                            S
                                        </div>

                                        <div>

                                            <strong>
                                                {siteName}
                                            </strong>

                                            <span>
                                                {report.url ||
                                                    "URL inconnue"}
                                            </span>

                                        </div>

                                    </div>

                                    {/* DATE */}

                                    <span className="history-date">

                                        {report.createdAt
                                            ? new Date(
                                                  report.createdAt
                                              ).toLocaleDateString(
                                                  "fr-FR"
                                              )
                                            : "—"}

                                    </span>

                                    {/* STATUS */}

                                    <span
                                        className={`history-status ${statusClassName}`}
                                    >

                                        <span className="status-dot" />

                                        {statusLabel}

                                    </span>

                                    {/* SCORE */}

                                    <span
                                        className={`history-score ${
                                            score >= 80
                                                ? "score-good"
                                                : score >=
                                                  50
                                                ? "score-mid"
                                                : "score-bad"
                                        }`}
                                    >

                                        {Number.isFinite(
                                            score
                                        )
                                            ? Math.round(
                                                  score
                                              )
                                            : "—"}

                                        {Number.isFinite(
                                            score
                                        ) && (
                                            <small>
                                                /100
                                            </small>
                                        )}

                                    </span>

                                    {/* REPORT BUTTON */}

                                    <Link
                                        to={`/audits/${report.id}`}
                                        className="history-report-button"
                                    >
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