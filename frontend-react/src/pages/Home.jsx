import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { useAuth } from "../components/AuthContext";

export default function Home() {
  const { user, runAudit, getAuditHistory } = useAuth();
  const [url, setUrl] = useState("");
  const [isRunning, setIsRunning] = useState(false);
  const [error, setError] = useState("");
  const [recentReports, setRecentReports] = useState([]);
  const [isLoadingRecent, setIsLoadingRecent] = useState(true);

  useEffect(() => {
    loadRecent();
  }, []);

  async function loadRecent() {
    setIsLoadingRecent(true);
    const result = await getAuditHistory();
    if (result.ok) {
      setRecentReports(result.reports.slice(0, 5));
    }
    setIsLoadingRecent(false);
  }

  async function handleAudit(e) {
    e.preventDefault();
    setError("");

    if (!url) {
      setError("3afak dkhel URL dyal site.");
      return;
    }

    setIsRunning(true);
    try {
      const result = await runAudit(url);
      if (!result.ok) {
        setError(result.error);
        return;
      }
      setUrl("");
      loadRecent();
    } finally {
      setIsRunning(false);
    }
  }

  return (
    <div className="dashboard">
      <div className="dashboard-header">
        <span className="eyebrow">Dashboard</span>
        <h1>Marhba, {user?.name || user?.email} 👋</h1>
        <p className="dashboard-subtitle">Dkhel URL dyal site bach tbda audit SEO jdid.</p>
      </div>

      <div className="audit-launch-card">
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
                <span className="spinner" aria-hidden="true" />
                Kaydir audit...
              </>
            ) : (
              "Bda Audit"
            )}
          </button>
        </form>
        {error && <p className="form-error">{error}</p>}
      </div>

      <div className="dashboard-section">
        <div className="dashboard-section-header">
          <h2>Akhir Audits</h2>
          <Link to="/history" className="link-small">
            Chouf koulchi →
          </Link>
        </div>

        {isLoadingRecent ? (
          <p className="page-loading">Kaytloaded...</p>
        ) : recentReports.length === 0 ? (
          <div className="empty-state">
            <p>Mazal madertich chi audit. Bda b'URL fo9.</p>
          </div>
        ) : (
          <div className="report-list">
            {recentReports.map((report) => (
              <div className="report-row" key={report.id || report.url}>
                <div className="report-row-main">
                  <span className="report-url">{report.url}</span>
                  <span className="report-date">
                    {report.createdAt
                      ? new Date(report.createdAt).toLocaleDateString()
                      : ""}
                  </span>
                </div>
                {typeof report.score === "number" && (
                  <span
                    className={`report-score ${
                      report.score >= 80
                        ? "score-good"
                        : report.score >= 50
                        ? "score-mid"
                        : "score-bad"
                    }`}
                  >
                    {report.score}
                  </span>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}