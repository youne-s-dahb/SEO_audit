import { useEffect, useState } from "react";
import { useAuth } from "../components/AuthContext";

export default function History() {
  const { getAuditHistory } = useAuth();
  const [reports, setReports] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");

  useEffect(() => {
    load();
  }, []);

  async function load() {
    setIsLoading(true);
    setError("");
    const result = await getAuditHistory();
    if (!result.ok) {
      setError(result.error);
    } else {
      setReports(result.reports);
    }
    setIsLoading(false);
  }

  const filtered = reports.filter((r) =>
    (r.url || "").toLowerCase().includes(search.toLowerCase())
  );

  return (
    <div className="dashboard">
      <div className="dashboard-header">
        <span className="eyebrow">Historique</span>
        <h1>Rapports dyal Audits</h1>
        <p className="dashboard-subtitle">
          Ga3 les audits SEO li dert 9bel, m3a score o date dyalhom.
        </p>
      </div>

      <div className="history-toolbar">
        <input
          type="text"
          placeholder="Search b URL..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="history-search"
        />
      </div>

      {isLoading ? (
        <p className="page-loading">Kaytloaded...</p>
      ) : error ? (
        <p className="form-error">{error}</p>
      ) : filtered.length === 0 ? (
        <div className="empty-state">
          <p>Makayn walo hna. {search ? "Jarreb search okhra." : "Bda audit jdid mn Dashboard."}</p>
        </div>
      ) : (
        <div className="report-table">
          <div className="report-table-head">
            <span>Site</span>
            <span>Date</span>
            <span>Statut</span>
            <span>Score</span>
          </div>
          {filtered.map((report) => (
            <div className="report-table-row" key={report.id || report.url}>
              <span className="report-url">{report.url}</span>
              <span className="report-date">
                {report.createdAt ? new Date(report.createdAt).toLocaleString() : "—"}
              </span>
              <span className={`report-status status-${report.status || "done"}`}>
                {report.status || "Termine"}
              </span>
              {typeof report.score === "number" ? (
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
              ) : (
                <span className="report-score">—</span>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}