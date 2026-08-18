import { useEffect, useState } from "react";
import "../style/AnalyseHistory.css";

const HISTORY_KEY =
  "seo_analyse_history";

function getToken() {
  const keys = [
    "token",
    "jwt_token",
    "jwtToken",
    "access_token",
    "accessToken",
  ];

  for (const key of keys) {
    const value =
      localStorage.getItem(key);

    if (value) {
      return value
        .replace(/^Bearer\s+/i, "")
        .trim();
    }
  }

  return null;
}

function hostOnly(url) {
  if (!url) return "—";

  try {
    return new URL(url)
      .hostname
      .replace(/^www\./, "");
  } catch {
    return url;
  }
}

function formatDate(date) {
  if (!date) return "—";

  const parsed =
    new Date(
      String(date).replace(
        " ",
        "T"
      )
    );

  if (
    Number.isNaN(
      parsed.getTime()
    )
  ) {
    return date;
  }

  return parsed.toLocaleDateString(
    "fr-FR",
    {
      day: "2-digit",
      month: "short",
      year: "numeric",
    }
  );
}

function formatTime(date) {
  if (!date) return "";

  const parsed =
    new Date(
      String(date).replace(
        " ",
        "T"
      )
    );

  if (
    Number.isNaN(
      parsed.getTime()
    )
  ) {
    return "";
  }

  return parsed.toLocaleTimeString(
    "fr-FR",
    {
      hour: "2-digit",
      minute: "2-digit",
    }
  );
}

function scoreClass(score) {
  if (score >= 90)
    return "excellent";

  if (score >= 75)
    return "good";

  if (score >= 50)
    return "warning";

  return "critical";
}

export default function AnalyseHistory({
  refreshKey = 0,
}) {
  const [history, setHistory] =
    useState([]);

  const [loading, setLoading] =
    useState(true);

  const [error, setError] =
    useState("");

  /* =======================================================
     LOCAL HISTORY
     ======================================================= */

  function loadLocalHistory() {
    try {
      const stored =
        localStorage.getItem(
          HISTORY_KEY
        );

      if (!stored) {
        return [];
      }

      const parsed =
        JSON.parse(stored);

      return Array.isArray(parsed)
        ? parsed
        : [];
    } catch {
      return [];
    }
  }

  function saveLocalHistory(items) {
    try {
      localStorage.setItem(
        HISTORY_KEY,
        JSON.stringify(items)
      );
    } catch {
      // ignore storage errors
    }
  }

  /* =======================================================
     LOAD
     ======================================================= */

  async function loadHistory() {
    setLoading(true);
    setError("");

    /*
     * Fallback local.
     * Cela permet à la page de fonctionner même
     * si aucun endpoint history n'est encore exposé.
     */
    const local =
      loadLocalHistory();

    setHistory(local);

    /*
     * Si ton backend possède un endpoint history,
     * on essaye de le récupérer.
     */
    const token =
      getToken();

    if (!token) {
      setLoading(false);
      return;
    }

    try {
      const response =
        await fetch(
          "http://localhost:8000/api/audit-onpage/history",
          {
            method: "GET",
            headers: {
              Accept:
                "application/json",
              Authorization:
                `Bearer ${token}`,
            },
          }
        );

      if (
        response.status === 404
      ) {
        setLoading(false);
        return;
      }

      const json =
        await response
          .json()
          .catch(() => ({}));

      if (!response.ok) {
        setLoading(false);
        return;
      }

      const serverData =
        Array.isArray(
          json
        )
          ? json
          : Array.isArray(
              json.data
            )
          ? json.data
          : Array.isArray(
              json.history
            )
          ? json.history
          : [];

      if (
        serverData.length > 0
      ) {
        setHistory(
          serverData
        );

        saveLocalHistory(
          serverData
        );
      }

    } catch (err) {
      /*
       * Pas d'erreur affichée ici :
       * localStorage sert de fallback.
       */
      console.debug(
        "Analyse history fallback:",
        err
      );
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadHistory();
  }, [refreshKey]);

  /* =======================================================
     DELETE
     ======================================================= */

  function handleDelete(id) {
    const confirmed =
      window.confirm(
        "Voulez-vous supprimer cette analyse de l’historique ?"
      );

    if (!confirmed) {
      return;
    }

    const updated =
      history.filter(
        (item) =>
          item.id !== id
      );

    setHistory(updated);

    saveLocalHistory(
      updated
    );
  }

  /* =======================================================
     CLEAR
     ======================================================= */

  function handleClear() {
    if (!history.length) {
      return;
    }

    const confirmed =
      window.confirm(
        "Voulez-vous supprimer tout l’historique des analyses ?"
      );

    if (!confirmed) {
      return;
    }

    setHistory([]);

    saveLocalHistory([]);
  }

  /* =======================================================
     RENDER
     ======================================================= */

  return (
    <section className="analyse-history">

      <div className="analyse-history-header">

        <div>
          <span className="analyse-history-eyebrow">
            Archives
          </span>

          <h2>
            Historique des analyses
          </h2>

          <p>
            Retrouvez les dernières pages
            analysées et leurs informations SEO.
          </p>
        </div>

        <div className="analyse-history-actions">

          <span className="analyse-history-count">
            {history.length}
          </span>

          {history.length > 0 && (
            <button
              type="button"
              onClick={
                handleClear
              }
            >
              Effacer
            </button>
          )}

        </div>

      </div>

      {loading && (
        <div className="analyse-history-loading">

          <span />

          Chargement de
          l’historique…

        </div>
      )}

      {!loading &&
        error && (
          <div className="analyse-history-error">
            {error}
          </div>
        )}

      {!loading &&
        !error &&
        history.length === 0 && (
          <div className="analyse-history-empty">

            <div className="analyse-history-empty-icon">
              ◷
            </div>

            <h3>
              Aucune analyse enregistrée
            </h3>

            <p>
              Les pages que vous analysez
              apparaîtront ici automatiquement.
            </p>

          </div>
        )}

      {!loading &&
        history.length > 0 && (
          <div className="analyse-history-list">

            {history.map(
              (item, index) => {

                const score =
                  Number(
                    item.score ??
                    item.global_score ??
                    0
                  );

                const url =
                  item.url ??
                  item.site_url ??
                  "";

                const title =
                  item.title ??
                  item.page_title ??
                  "Analyse On-Page";

                const checkedAt =
                  item.analysis_date ??
                  item.checked_at ??
                  item.created_at;

                return (
                  <article
                    className="analyse-history-item"
                    key={
                      item.id ??
                      `${url}-${index}`
                    }
                  >

                    <div className="analyse-history-number">
                      {String(
                        index + 1
                      ).padStart(
                        2,
                        "0"
                      )}
                    </div>

                    <div className="analyse-history-main">

                      <strong
                        title={
                          title
                        }
                      >
                        {title}
                      </strong>

                      <span
                        title={
                          url
                        }
                      >
                        {hostOnly(
                          url
                        )}
                      </span>

                      <small>
                        {formatDate(
                          checkedAt
                        )}
                        {" · "}
                        {formatTime(
                          checkedAt
                        )}
                      </small>

                    </div>

                    <div className="analyse-history-score">

                      <span
                        className={
                          scoreClass(
                            score
                          )
                        }
                      >
                        {score}
                      </span>

                      <small>
                        /100
                      </small>

                    </div>

                    <div className="analyse-history-status">

                      <span>
                        {item.status ===
                        "success"
                          ? "Analyse terminée"
                          : "On-Page"}
                      </span>

                    </div>

                    <button
                      type="button"
                      className="analyse-history-delete"
                      onClick={() =>
                        handleDelete(
                          item.id ??
                          `${url}-${index}`
                        )
                      }
                    >
                      ×
                    </button>

                  </article>
                );
              }
            )}

          </div>
        )}

    </section>
  );
}