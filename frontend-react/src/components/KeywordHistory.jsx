import { useEffect, useState } from "react";
import "../style/History.css";

const API_URL = "http://localhost:8000/api/keyword-ranking";

/* =========================================================
   TOKEN
========================================================= */

function getToken() {
  const possibleKeys = [
    "token",
    "jwt_token",
    "jwtToken",
    "access_token",
    "accessToken",
  ];

  for (const key of possibleKeys) {
    const value = localStorage.getItem(key);

    if (value) {
      return value.replace(/^Bearer\s+/i, "").trim();
    }
  }

  return null;
}

/* =========================================================
   DATE
========================================================= */

function parseDate(date) {
  if (!date) return null;

  const normalized = String(date).replace(" ", "T");
  const d = new Date(normalized);

  if (Number.isNaN(d.getTime())) {
    return null;
  }

  return d;
}

function formatDate(date) {
  const d = parseDate(date);

  if (!d) return "—";

  return d.toLocaleDateString("fr-FR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
}

function formatTime(date) {
  const d = parseDate(date);

  if (!d) return "";

  return d.toLocaleTimeString("fr-FR", {
    hour: "2-digit",
    minute: "2-digit",
  });
}

/* =========================================================
   HOST
========================================================= */

function hostOnly(url) {
  if (!url) return "—";

  try {
    return new URL(url).hostname.replace(/^www\./i, "");
  } catch {
    return String(url)
      .replace(/^https?:\/\//i, "")
      .replace(/^www\./i, "")
      .split("/")[0];
  }
}

/* =========================================================
   COMPONENT
========================================================= */

export default function KeywordHistory({ refreshKey = 0 }) {
  const [history, setHistory] = useState([]);

  const [loading, setLoading] = useState(true);

  const [loadingProgress, setLoadingProgress] =
    useState(0);

  const [deletingId, setDeletingId] =
    useState(null);

  const [error, setError] = useState("");

  /* =======================================================
     LOAD HISTORY
  ======================================================= */

  async function loadHistory() {
    const token = getToken();

    if (!token) {
      setError(
        "Session expirée. Veuillez vous reconnecter."
      );

      setHistory([]);
      setLoading(false);

      return;
    }

    setLoading(true);
    setError("");
    setLoadingProgress(0);

    let progress = 0;

    /*
     * Animation 0 → 90%
     *
     * Le 100% arrive uniquement
     * lorsque le backend a répondu.
     */

    const progressInterval = setInterval(() => {
      progress += Math.random() * 8;

      if (progress >= 90) {
        progress = 90;
        clearInterval(progressInterval);
      }

      setLoadingProgress(
        Math.floor(progress)
      );
    }, 180);

    try {
      const response = await fetch(
        `${API_URL}/history`,
        {
          method: "GET",

          headers: {
            Accept: "application/json",

            Authorization:
              `Bearer ${token}`,
          },
        }
      );

      const json =
        await response.json();

      if (!response.ok) {
        throw new Error(
          json.message ||
            "Impossible de charger l'historique."
        );
      }

      const data =
        Array.isArray(json.data)
          ? json.data
          : [];

      setHistory(data);

      /*
       * Backend terminé
       */

      clearInterval(progressInterval);

      setLoadingProgress(100);

      /*
       * Garder 100% visible
       * pendant 500ms
       */

      await new Promise(
        (resolve) =>
          setTimeout(resolve, 500)
      );

    } catch (err) {
      console.error(
        "Keyword history error:",
        err
      );

      clearInterval(progressInterval);

      setError(
        err.message ||
          "Impossible de charger l'historique."
      );

      setHistory([]);

    } finally {
      clearInterval(progressInterval);

      setLoading(false);
    }
  }

  /* =======================================================
     EFFECT
  ======================================================= */

  useEffect(() => {
    loadHistory();

    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [refreshKey]);

  /* =======================================================
     DELETE
  ======================================================= */

  async function handleDelete(id) {
    const token = getToken();

    if (!token) {
      setError(
        "Session expirée. Veuillez vous reconnecter."
      );

      return;
    }

    const confirmed =
      window.confirm(
        "Voulez-vous vraiment supprimer ce classement ?"
      );

    if (!confirmed) {
      return;
    }

    try {
      setDeletingId(id);
      setError("");

      const response = await fetch(
        `${API_URL}/${id}`,
        {
          method: "DELETE",

          headers: {
            Accept: "application/json",

            Authorization:
              `Bearer ${token}`,
          },
        }
      );

      const json =
        await response
          .json()
          .catch(() => ({}));

      if (!response.ok) {
        throw new Error(
          json.message ||
            "Impossible de supprimer le classement."
        );
      }

      setHistory((previous) =>
        previous.filter(
          (item) =>
            item.id !== id
        )
      );

    } catch (err) {
      console.error(
        "Delete ranking error:",
        err
      );

      setError(
        err.message ||
          "Impossible de supprimer le classement."
      );

    } finally {
      setDeletingId(null);
    }
  }

  /* =======================================================
     RENDER
  ======================================================= */

  return (
    <section className="kw-history">

      {/* ===================================================
          HEADER
      =================================================== */}

      <div className="kw-history-header">

        <div className="kw-history-heading">

          <span className="kw-history-eyebrow">
            Suivi SERP
          </span>

          <h2 className="kw-history-title">
            Historique des mots-clés
          </h2>

          <p className="kw-history-subtitle">
            Retrouvez tous les classements vérifiés.
          </p>

        </div>

        {!loading && (
          <div className="kw-history-count">
            {history.length}
          </div>
        )}

      </div>


      {/* ===================================================
          LOADING
          Même style que History
      =================================================== */}

      {loading && (
        <div className="history-loading-card">

          <div className="history-loading-content">

            {/* CIRCLE */}

            <div
              className="history-progress-circle"
              style={{
                "--progress":
                  `${loadingProgress * 3.6}deg`,
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
                  ? "Récupération des classements..."

                  : loadingProgress < 90
                  ? "Préparation de l'historique..."

                  : loadingProgress < 100
                  ? "Presque terminé..."

                  : "Historique chargé !"}
              </h3>

              <p>
                Nous récupérons vos
                derniers classements
                de mots-clés.
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


      {/* ===================================================
          ERROR
      =================================================== */}

      {!loading && error && (
        <div className="kw-history-error">

          <div>
            {error}
          </div>

        </div>
      )}


      {/* ===================================================
          EMPTY
      =================================================== */}

      {!loading &&
        !error &&
        history.length === 0 && (

          <div className="kw-history-empty">

            <div className="kw-history-empty-icon">
              ↗
            </div>

            <h3 className="kw-history-empty-title">
              Aucun classement enregistré
            </h3>

            <p className="kw-history-empty-text">
              Les mots-clés que vous vérifiez
              apparaîtront ici automatiquement.
            </p>

          </div>
        )}


      {/* ===================================================
          HISTORY
      =================================================== */}

      {!loading &&
        !error &&
        history.length > 0 && (

          <div className="kw-history-list">

            {history.map((item) => {

              const position =
                Number(item.position);

              const isTop3 =
                Number.isFinite(position) &&
                position >= 1 &&
                position <= 3;

              return (

                <div
                  className={
                    `kw-history-item${
                      isTop3
                        ? " is-top3"
                        : ""
                    }`
                  }

                  key={item.id}
                >

                  {/* =====================================
                      MAIN
                  ===================================== */}

                  <div className="kw-history-main">

                    <div className="kw-history-icon">

                      {item.keyword
                        ? String(
                            item.keyword
                          )
                            .charAt(0)
                            .toUpperCase()
                        : "?"}

                    </div>


                    <div className="kw-history-content">

                      <div
                        className="kw-history-keyword"
                        title={
                          item.keyword ||
                          ""
                        }
                      >
                        {item.keyword ||
                          "Mot-clé inconnu"}
                      </div>


                      <div
                        className="kw-history-site"
                        title={
                          item.site ||
                          ""
                        }
                      >

                        {hostOnly(
                          item.site
                        )}

                        {" · "}

                        {item.search_engine ||
                          "Google"}

                        {" · "}

                        {item.device ||
                          "Mobile"}

                      </div>

                    </div>

                  </div>


                  {/* =====================================
                      POSITION
                  ===================================== */}

                  <div className="kw-history-position">

                    <span
                      className={
                        `kw-history-position-number${
                          isTop3
                            ? " top3"
                            : ""
                        }`
                      }
                    >
                      #
                      {item.position ??
                        "—"}
                    </span>

                    <span className="kw-history-position-label">
                      Page{" "}
                      {item.search_page ??
                        "—"}
                    </span>

                  </div>


                  {/* =====================================
                      ACTION
                  ===================================== */}

                  <div className="kw-history-action">

                    <button
                      type="button"
                      className="kw-history-delete"

                      onClick={() =>
                        handleDelete(
                          item.id
                        )
                      }

                      disabled={
                        deletingId ===
                        item.id
                      }
                    >

                      {deletingId ===
                      item.id
                        ? "Suppression..."
                        : "Supprimer"}

                    </button>


                    {/* DATE */}

                    <div className="kw-history-date">

                      <span className="kw-history-date-label">
                        Vérifié
                      </span>

                      <span className="kw-history-date-value">

                        {formatDate(
                          item.checked_at
                        )}

                        {" · "}

                        {formatTime(
                          item.checked_at
                        )}

                      </span>

                    </div>

                  </div>

                </div>
              );
            })}

          </div>
        )}

    </section>
  );
}