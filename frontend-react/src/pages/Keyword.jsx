import { useEffect, useState } from "react";
import "../style/Keyword.css";

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
   HELPERS
   ========================================================= */

function hostOnly(url) {
  if (!url) return "—";

  try {
    return new URL(url).hostname.replace(/^www\./, "");
  } catch {
    return url;
  }
}

function formatDate(date) {
  if (!date) return "—";

  const normalized = String(date).replace(" ", "T");
  const d = new Date(normalized);

  if (Number.isNaN(d.getTime())) {
    return date;
  }

  return d.toLocaleDateString("fr-FR", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
}

function formatTime(date) {
  if (!date) return "";

  const normalized = String(date).replace(" ", "T");
  const d = new Date(normalized);

  if (Number.isNaN(d.getTime())) {
    return "";
  }

  return d.toLocaleTimeString("fr-FR", {
    hour: "2-digit",
    minute: "2-digit",
  });
}

function normalizePosition(value) {
  if (value === null || value === undefined || value === "") {
    return null;
  }

  const number = Number(value);

  return Number.isFinite(number) ? number : null;
}

function getPositionFromResponse(data) {
  return normalizePosition(
    data?.position ??
      data?.ranking ??
      data?.rank ??
      data?.result?.position ??
      data?.data?.position
  );
}

function getSearchPage(position, data) {
  if (data?.search_page !== undefined) {
    return data.search_page;
  }

  if (data?.page !== undefined) {
    return data.page;
  }

  if (position) {
    return Math.ceil(position / 10);
  }

  return null;
}

/* =========================================================
   LOADING PROGRESS
   ========================================================= */

function KeywordLoading({ progress }) {
  let title = "Préparation de l'analyse";
  let description = "Initialisation de la vérification…";

  if (progress >= 20 && progress < 45) {
    title = "Recherche Google";
    description = "Recherche du mot-clé dans les résultats SERP…";
  }

  if (progress >= 45 && progress < 70) {
    title = "Analyse des résultats";
    description = "Analyse des positions trouvées…";
  }

  if (progress >= 70 && progress < 90) {
    title = "Vérification du site";
    description = "Vérification de votre domaine dans les résultats…";
  }

  if (progress >= 90 && progress < 100) {
    title = "Finalisation";
    description = "Préparation de votre résultat…";
  }

  if (progress >= 100) {
    title = "Analyse terminée";
    description = "Résultat prêt.";
  }

  return (
    <section className="kw-progress-loading">
      <div className="kw-progress-top">
        <div>
          <span className="kw-progress-eyebrow">
            Analyse SERP
          </span>

          <h2 className="kw-progress-title">
            {title}
          </h2>

          <p className="kw-progress-description">
            {description}
          </p>
        </div>

        <div className="kw-progress-number">
          {progress}
          <span>%</span>
        </div>
      </div>

      <div className="kw-progress-bar">
        <div
          className="kw-progress-bar-fill"
          style={{
            width: `${progress}%`,
          }}
        />
      </div>

      <div className="kw-progress-footer">
        <span>0</span>

        <span className="kw-progress-current">
          {progress}% analysé
        </span>

        <span>100</span>
      </div>

      <div className="kw-progress-steps">
        <div
          className={
            progress >= 20
              ? "kw-progress-step active"
              : "kw-progress-step"
          }
        >
          <span>01</span>
          Préparation
        </div>

        <div
          className={
            progress >= 45
              ? "kw-progress-step active"
              : "kw-progress-step"
          }
        >
          <span>02</span>
          Recherche
        </div>

        <div
          className={
            progress >= 70
              ? "kw-progress-step active"
              : "kw-progress-step"
          }
        >
          <span>03</span>
          Analyse
        </div>

        <div
          className={
            progress >= 90
              ? "kw-progress-step active"
              : "kw-progress-step"
          }
        >
          <span>04</span>
          Finalisation
        </div>
      </div>
    </section>
  );
}

/* =========================================================
   MAIN
   ========================================================= */

export default function Keyword() {
  const [keyword, setKeyword] = useState("");
  const [siteUrl, setSiteUrl] = useState("");

  const [loading, setLoading] = useState(false);

  const [progress, setProgress] = useState(0);

  const [result, setResult] = useState(null);
  const [resultState, setResultState] = useState("");

  const [error, setError] = useState("");

  const [refreshKey, setRefreshKey] = useState(0);

  /* =======================================================
     PROGRESS ANIMATION
     ======================================================= */

  useEffect(() => {
    if (!loading) {
      setProgress(0);
      return;
    }

    setProgress(0);

    const interval = setInterval(() => {
      setProgress((current) => {
        /*
         * On avance rapidement au début,
         * puis doucement vers 95%.
         *
         * On ne met jamais 100% ici.
         * Le 100% arrive quand l'API répond.
         */

        if (current >= 95) {
          return 95;
        }

        if (current < 20) {
          return current + 2;
        }

        if (current < 45) {
          return current + 1;
        }

        if (current < 70) {
          return current + 1;
        }

        if (current < 90) {
          return current + 1;
        }

        return current + 0.5;
      });
    }, 180);

    return () => clearInterval(interval);
  }, [loading]);

  /* =======================================================
     SUBMIT
     ======================================================= */

  async function handleSubmit(event) {
    event.preventDefault();

    const cleanKeyword = keyword.trim();
    const cleanUrl = siteUrl.trim();

    if (!cleanKeyword) {
      setError("Veuillez saisir un mot-clé.");
      setResult(null);
      setResultState("error");
      return;
    }

    if (!cleanUrl) {
      setError("Veuillez saisir l'URL de votre site.");
      setResult(null);
      setResultState("error");
      return;
    }

    const token = getToken();

    if (!token) {
      setError("Session expirée. Veuillez vous reconnecter.");
      setResult(null);
      setResultState("error");
      return;
    }

    try {
      setLoading(true);
      setProgress(5);

      setError("");
      setResult(null);
      setResultState("loading");

      const response = await fetch(API_URL, {
        method: "POST",

        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },

        body: JSON.stringify({
          keyword: cleanKeyword,
          site_url: cleanUrl,
        }),
      });

      const json = await response.json().catch(() => ({}));

      if (!response.ok) {
        throw new Error(
          json.message ||
            json.error ||
            "Impossible de vérifier le classement."
        );
      }

      const data = json.data ?? json;

      const position = getPositionFromResponse(data);

      /*
       * API répondue.
       * On affiche 100% juste avant d'afficher le résultat.
       */

      setProgress(100);

      await new Promise((resolve) =>
        setTimeout(resolve, 450)
      );

      setResult({
        ...data,

        keyword:
          data.keyword ??
          cleanKeyword,

        site_url:
          data.site_url ??
          data.site ??
          data.url ??
          cleanUrl,

        position,

        search_page:
          getSearchPage(position, data),

        search_engine:
          data.search_engine ??
          data.engine ??
          "Google",

        device:
          data.device ??
          "Desktop",

        checked_at:
          data.checked_at ??
          data.created_at ??
          new Date().toISOString(),
      });

      if (position === null) {
        setResultState("notfound");
      } else {
        setResultState("success");
      }

      setRefreshKey((value) => value + 1);

    } catch (err) {
      console.error(
        "Keyword ranking error:",
        err
      );

      setError(
        err.message ||
          "Une erreur est survenue pendant la vérification."
      );

      setResult(null);
      setResultState("error");

    } finally {
      setLoading(false);
    }
  }

  /* =======================================================
     RESET
     ======================================================= */

  function handleReset() {
    setKeyword("");
    setSiteUrl("");
    setResult(null);
    setError("");
    setResultState("");
    setProgress(0);
  }

  /* =======================================================
     RESULT DATA
     ======================================================= */

  const position = result?.position ?? null;

  const isTop3 =
    position !== null &&
    position <= 3;

  return (
    <>
      {/* =====================================================
          INLINE LOADING STYLE
          ===================================================== */}

      <style>{`
        .kw-progress-loading {
          width: 95%;
          margin-top: 18px;
          margin-left: 20px;
          padding: 25px;
          border: 1px solid rgba(246,244,238,.10);
          border-radius: 20px;
          background:
            linear-gradient(
              145deg,
              rgba(255,255,255,.035),
              rgba(255,255,255,.008)
            ),
            #20242e;
          box-shadow: 0 18px 45px rgba(0,0,0,.14);
          animation: kwProgressAppear .35s ease both;
        }

        @keyframes kwProgressAppear {
          from {
            opacity: 0;
            transform: translateY(10px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }

        .kw-progress-top {
          display: flex;
          align-items: center;
          justify-content: space-between;
          gap: 20px;
        }

        .kw-progress-eyebrow {
          display: block;
          margin-bottom: 5px;
          color: #e0a458;
          font-size: 9px;
          font-weight: 800;
          letter-spacing: .16em;
          text-transform: uppercase;
        }

        .kw-progress-title {
          margin: 0;
          color: #f6f4ee;
          font-family: "Fraunces", serif;
          font-size: 21px;
          font-weight: 500;
        }

        .kw-progress-description {
          margin: 5px 0 0;
          color: rgba(246,244,238,.45);
          font-size: 10px;
        }

        .kw-progress-number {
          flex: 0 0 auto;
          color: #efbd7a;
          font-family: "Fraunces", serif;
          font-size: 46px;
          line-height: 1;
          font-weight: 600;
          letter-spacing: -.04em;
        }

        .kw-progress-number span {
          margin-left: 2px;
          color: rgba(246,244,238,.42);
          font-family: "Inter", sans-serif;
          font-size: 14px;
          font-weight: 700;
        }

        .kw-progress-bar {
          position: relative;
          width: 100%;
          height: 8px;
          margin-top: 23px;
          overflow: hidden;
          border-radius: 999px;
          background: #1d212c;
          border: 1px solid rgba(246,244,238,.07);
        }

        .kw-progress-bar-fill {
          position: relative;
          height: 100%;
          min-width: 0;
          border-radius: inherit;
          background: linear-gradient(
            90deg,
            #c9884a,
            #e0a458,
            #efbd7a
          );
          box-shadow: 0 0 15px rgba(224,164,88,.25);
          transition: width .18s ease;
        }

        .kw-progress-bar-fill::after {
          content: "";
          position: absolute;
          top: 0;
          right: 0;
          width: 70px;
          height: 100%;
          background: linear-gradient(
            90deg,
            transparent,
            rgba(255,255,255,.45)
          );
          animation: kwProgressShine 1.1s ease-in-out infinite;
        }

        @keyframes kwProgressShine {
          from {
            opacity: .2;
            transform: translateX(20px);
          }
          to {
            opacity: .8;
            transform: translateX(0);
          }
        }

        .kw-progress-footer {
          display: flex;
          align-items: center;
          justify-content: space-between;
          margin-top: 7px;
          color: rgba(246,244,238,.28);
          font-size: 8px;
          font-weight: 700;
        }

        .kw-progress-current {
          color: rgba(246,244,238,.55);
          text-transform: uppercase;
          letter-spacing: .08em;
        }

        .kw-progress-steps {
          display: grid;
          grid-template-columns: repeat(4, 1fr);
          gap: 7px;
          margin-top: 21px;
        }

        .kw-progress-step {
          display: flex;
          flex-direction: column;
          gap: 4px;
          padding: 9px 10px;
          border: 1px solid rgba(246,244,238,.06);
          border-radius: 9px;
          color: rgba(246,244,238,.28);
          background: rgba(255,255,255,.012);
          font-size: 8px;
          text-transform: uppercase;
          letter-spacing: .04em;
          transition:
            color .25s ease,
            border-color .25s ease,
            background .25s ease;
        }

        .kw-progress-step span {
          color: rgba(246,244,238,.18);
          font-size: 7px;
          font-weight: 800;
        }

        .kw-progress-step.active {
          border-color: rgba(224,164,88,.22);
          background: rgba(224,164,88,.07);
          color: #efbd7a;
        }

        .kw-progress-step.active span {
          color: #e0a458;
        }

        @media (max-width: 560px) {
          .kw-progress-loading {
            padding: 19px;
          }

          .kw-progress-number {
            font-size: 36px;
          }

          .kw-progress-title {
            font-size: 18px;
          }

          .kw-progress-steps {
            grid-template-columns: repeat(2, 1fr);
          }
        }
      `}</style>

      <main className="kw-wrap">

        {/* ===================================================
            HERO
            =================================================== */}

        <header className="kw-hero">

          <div className="kw-eyebrow">
            Suivi SERP
          </div>

          <h1 className="kw-title">
            Vérifiez votre position
            <span> Google.</span>
          </h1>

          <p className="kw-lede">
            Analysez rapidement le classement de votre site
            pour un mot-clé donné et suivez son évolution
            dans votre historique.
          </p>

        </header>


        {/* ===================================================
            SEARCH CARD
            =================================================== */}

        <section className="kw-card">

          <div className="kw-card-header">

            <div>
              <span className="kw-card-kicker">
                Vérification
              </span>

              <h2>
                Rechercher un classement
              </h2>
            </div>

            <div className="kw-card-status">
              <span />
              SERP
            </div>

          </div>


          <form
            className="kw-form"
            onSubmit={handleSubmit}
          >

            {/* KEYWORD */}

            <div className="kw-field">

              <label htmlFor="keyword">
                Mot-clé
              </label>

              <div className="kw-input-wrapper">

                <span className="kw-input-icon">
                  #
                </span>

                <input
                  id="keyword"
                  type="text"
                  value={keyword}
                  onChange={(event) =>
                    setKeyword(event.target.value)
                  }
                  placeholder="ex. agence immobilière maroc"
                  autoComplete="off"
                  disabled={loading}
                />

              </div>

              <span className="kw-field-hint">
                Le terme exact que vous souhaitez analyser.
              </span>

            </div>


            {/* URL */}

            <div className="kw-field">

              <label htmlFor="site-url">
                URL du site
              </label>

              <div className="kw-input-wrapper">

                <span className="kw-input-icon kw-url-icon">
                  ↗
                </span>

                <input
                  id="site-url"
                  type="url"
                  value={siteUrl}
                  onChange={(event) =>
                    setSiteUrl(event.target.value)
                  }
                  placeholder="https://www.example.ma"
                  autoComplete="url"
                  disabled={loading}
                />

              </div>

              <span className="kw-field-hint">
                Entrez l'adresse du site à rechercher dans Google.
              </span>

            </div>


            {/* ERROR */}

            {error && (
              <div className="kw-form-error">

                <span className="kw-form-error-icon">
                  !
                </span>

                <span>
                  {error}
                </span>

              </div>
            )}


            {/* BUTTON */}

            <div className="kw-form-actions">

              <button
                type="submit"
                className="kw-submit"
                disabled={loading}
              >

                {loading ? (
                  <>
                    Analyse en cours
                    <span>
                      {progress}%
                    </span>
                  </>
                ) : (
                  <>
                    Vérifier le classement

                    <span className="kw-submit-arrow">
                      →
                    </span>
                  </>
                )}

              </button>

              {(keyword || siteUrl || result) &&
                !loading && (
                  <button
                    type="button"
                    className="kw-reset"
                    onClick={handleReset}
                  >
                    Réinitialiser
                  </button>
                )}

            </div>

          </form>


          <div className="kw-footer-note">

            <span className="kw-note-dot" />

            Les résultats sont basés sur les données SERP
            disponibles au moment de la vérification.

          </div>

        </section>


        {/* ===================================================
            NEW LOADING
            =================================================== */}

        {loading && (
          <KeywordLoading
            progress={Math.round(progress)}
          />
        )}


        {/* ===================================================
            RESULT
            =================================================== */}

        {!loading && result && (

          <section
            className={`kw-result ${
              resultState === "success"
                ? "state-success"
                : "state-notfound"
            }`}
          >

            <div className="kw-result-header">

              <div>

                <span className="kw-result-eyebrow">
                  Résultat de la vérification
                </span>

                <h2>
                  Votre classement
                </h2>

              </div>

              <span
                className={`kw-pill ${
                  resultState === "success"
                    ? "success"
                    : "notfound"
                }`}
              >
                {resultState === "success"
                  ? "Trouvé"
                  : "Non trouvé"}
              </span>

            </div>


            <div className="kw-result-main">

              <div className="kw-result-site">

                <span className="kw-label">
                  Mot-clé
                </span>

                <strong className="kw-value">
                  {result.keyword}
                </strong>

                <span className="kw-result-domain">
                  {hostOnly(result.site_url)}
                </span>

              </div>


              <div className="kw-position-block">

                <span className="kw-label">
                  Position
                </span>

                <div
                  className={`kw-position-num ${
                    isTop3 ? "top3" : ""
                  }`}
                >
                  {position !== null
                    ? `#${position}`
                    : "—"}
                </div>

                <span className="kw-position-sub">

                  {position !== null
                    ? `Page ${
                        result.search_page ?? "—"
                      }`
                    : "Hors classement"}

                </span>

              </div>

            </div>


           


            {/* META */}

            <div className="kw-meta-row">

              <div className="kw-meta-item">
                <span>Moteur</span>
                <b>
                  {result.search_engine ||
                    "Google"}
                </b>
              </div>

              <div className="kw-meta-item">
                <span>Appareil</span>
                <b>
                  {result.device ||
                    "Desktop"}
                </b>
              </div>

              <div className="kw-meta-item">
                <span>Page</span>
                <b>
                  {result.search_page ??
                    "—"}
                </b>
              </div>

              <div className="kw-meta-item">
                <span>Vérifié</span>
                <b>
                  {formatDate(
                    result.checked_at
                  )}
                  {" · "}
                  {formatTime(
                    result.checked_at
                  )}
                </b>
              </div>

            </div>

          </section>
        )}


        {/* ===================================================
            HISTORY
            =================================================== */}

        <KeywordHistory
          refreshKey={refreshKey}
        />

      </main>
    </>
  );
}


/* =========================================================
   HISTORY COMPONENT
   ========================================================= */

function KeywordHistory({
  refreshKey = 0,
}) {

  const [history, setHistory] =
    useState([]);

  const [loading, setLoading] =
    useState(true);

  const [deletingId, setDeletingId] =
    useState(null);

  const [error, setError] =
    useState("");


  /* =======================================================
     LOAD HISTORY
     ======================================================= */

  async function loadHistory() {

    const token = getToken();

    if (!token) {
      setError(
        "Session expirée. Veuillez vous reconnecter."
      );

      setLoading(false);
      return;
    }

    try {

      setLoading(true);
      setError("");

      const response = await fetch(
        `${API_URL}/history`,
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

      const json =
        await response.json();

      if (!response.ok) {
        throw new Error(
          json.message ||
            "Impossible de charger l'historique."
        );
      }

      setHistory(
        json.data || []
      );

    } catch (err) {

      console.error(
        "History error:",
        err
      );

      setError(
        err.message ||
          "Erreur de chargement."
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

      const response =
        await fetch(
          `${API_URL}/${id}`,
          {
            method: "DELETE",

            headers: {
              Accept:
                "application/json",

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

      setHistory((prev) =>
        prev.filter(
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
          "Erreur de suppression."
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

        <div className="kw-history-count">
          {history.length}
        </div>

      </div>


      {/* ===================================================
          HISTORY LOADING
          =================================================== */}

      {loading && (

        <div className="kw-history-loading">

          <span className="kw-history-spinner" />

          <span>
            Chargement de l'historique…
          </span>

        </div>

      )}


      {/* ===================================================
          ERROR
          =================================================== */}

      {!loading && error && (

        <div className="kw-history-error">
          {error}
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
          LIST
          =================================================== */}

      {!loading &&
        !error &&
        history.length > 0 && (

          <div className="kw-history-list">

            {history.map((item) => {

              const position =
                normalizePosition(
                  item.position
                );

              const isTop3 =
                position !== null &&
                position <= 3;

              return (

                <article
                  className={`kw-history-item ${
                    isTop3
                      ? "is-top3"
                      : ""
                  }`}
                  key={item.id}
                >

                  {/* MAIN */}

                  <div className="kw-history-main">

                    <div className="kw-history-icon">

                      {item.keyword
                        ? item.keyword
                            .charAt(0)
                            .toUpperCase()
                        : "?"}

                    </div>


                    <div className="kw-history-content">

                      <div
                        className="kw-history-keyword"
                        title={item.keyword}
                      >
                        {item.keyword ||
                          "Mot-clé inconnu"}
                      </div>

                      <div
                        className="kw-history-site"
                        title={item.site}
                      >
                        {hostOnly(
                          item.site
                        )}
                      </div>


                      <div className="kw-history-tags">

                        <span className="kw-history-tag">
                          {item.search_engine ||
                            "Google"}
                        </span>

                        <span className="kw-history-tag">
                          {item.device ||
                            "Desktop"}
                        </span>

                      </div>

                    </div>

                  </div>


                  {/* POSITION */}

                  <div className="kw-history-position">

                    <span
                      className={`kw-history-position-number ${
                        isTop3
                          ? "top3"
                          : ""
                      }`}
                    >
                      #
                      {position ?? "—"}
                    </span>

                    <span className="kw-history-position-label">
                      Position
                    </span>

                  </div>


                  {/* ACTION */}

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
                      item.id ? (

                        <span className="kw-delete-spinner" />

                      ) : (

                        <>
                          <span className="kw-delete-icon">
                            ×
                          </span>

                          Supprimer
                        </>

                      )}

                    </button>


                    <div className="kw-history-date">

                      <span className="kw-history-date-label">
                        Vérifié
                      </span>

                      <span className="kw-history-date-value">
                        {formatDate(
                          item.checked_at
                        )}
                      </span>

                      <span className="kw-history-time">
                        {formatTime(
                          item.checked_at
                        )}
                      </span>

                    </div>

                  </div>

                </article>

              );
            })}

          </div>

        )}

    </section>
  );
}