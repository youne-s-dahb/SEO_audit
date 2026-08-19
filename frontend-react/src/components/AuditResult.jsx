import AuditPagesReport from "./AuditPagesReport";

export default function AuditResult({ audit }) {

    if (!audit) {
        return null;
    }

    const score = audit.global_score ?? 0;

    const getScoreClass = (score) => {
        if (score >= 80) return "score-good";
        if (score >= 50) return "score-mid";
        return "score-bad";
    };

    const formatTime = (ms) => {
        if (!ms) return "—";

        return `${(ms / 1000).toFixed(1)} s`;
    };

    const metrics = audit.metrics || {};

    return (
        <section className="audit-result">

            {/* HEADER */}

            <div className="audit-result-header">

                <div>
                    <span className="eyebrow">
                        Audit terminé
                    </span>

                    <h2>
                        Résultat de l'analyse
                    </h2>

                    <p className="audit-result-url">
                        {audit.url}
                    </p>
                </div>

                <div
                    className={`global-score ${getScoreClass(score)}`}
                >
                    <span className="global-score-number">
                        {score}
                    </span>

                    <span className="global-score-label">
                        / 100
                    </span>
                </div>

            </div>


            {/* MAIN SCORES */}

            <div className="score-grid">

                <ScoreCard
                    title="Performance"
                    value={audit.pagespeed_desktop_score}
                    subtitle="Desktop"
                />

                <ScoreCard
                    title="Performance"
                    value={audit.pagespeed_mobile_score}
                    subtitle="Mobile"
                />

                <ScoreCard
                    title="SEO"
                    value={audit.seo_score}
                />

                <ScoreCard
                    title="Accessibility"
                    value={audit.accessibility_score}
                />

                <ScoreCard
                    title="Best Practices"
                    value={audit.best_practices_score}
                />

            </div>


            {/* PERFORMANCE */}

            <div className="result-card">

                <div className="result-card-header">
                    <h3>
                        Performance
                    </h3>
                </div>

                <div className="metrics-grid">

                    <Metric
                        label="First Contentful Paint"
                        value={
                            metrics.first_contentful_paint
                        }
                    />

                    <Metric
                        label="Largest Contentful Paint"
                        value={
                            metrics.largest_contentful_paint
                        }
                    />

                    <Metric
                        label="Speed Index"
                        value={
                            metrics.speed_index
                        }
                    />

                    <Metric
                        label="Total Blocking Time"
                        value={
                            metrics.total_blocking_time
                        }
                    />

                    <Metric
                        label="Cumulative Layout Shift"
                        value={
                            metrics.cumulative_layout_shift
                        }
                    />

                    <Metric
                        label="Time To Interactive"
                        value={
                            metrics.time_to_interactive
                        }
                    />

                </div>

            </div>


            


            {/* TECHNICAL SEO */}

            <div className="result-card">

                <div className="result-card-header">
                    <h3>
                        Technical SEO
                    </h3>
                </div>

                <div className="checks-grid">

                    <Check
                        label="HTTPS"
                        value={audit.https}
                    />

                    <Check
                        label="Robots.txt"
                        value={audit.robots_txt}
                    />

                    <Check
                        label="Sitemap.xml"
                        value={audit.sitemap_xml}
                    />

                    <Check
                        label="Mobile Friendly"
                        value={audit.mobile_friendly}
                    />

                </div>

            </div>


            {/* LOAD TIME */}

            <div className="result-card">

                <div className="load-time">

                    <div>
                        <span className="metric-label">
                            Temps de chargement
                        </span>

                        <strong>
                            {formatTime(
                                audit.page_load_time_ms
                            )}
                        </strong>
                    </div>

                    <div className="load-time-icon">
                        ⚡
                    </div>

                </div>

            </div>


            {/* RECOMMENDATIONS */}

            {audit.recommendations &&
                audit.recommendations.length > 0 && (

                    <div className="result-card">

                        <div className="result-card-header">
                            <h3>
                                Recommandations
                            </h3>
                        </div>

                        <div className="recommendations">

                            {audit.recommendations.map(
                                (recommendation, index) => (

                                    <div
                                        className="recommendation"
                                        key={index}
                                    >
                                        <span>
                                            {index + 1}
                                        </span>

                                        <p>
                                            {recommendation}
                                        </p>
                                    </div>

                                )
                            )}

                        </div>

                    </div>

                )}

        </section>
    );
}


/* =========================
   SCORE CARD
========================= */

function ScoreCard({
    title,
    value,
    subtitle
}) {

    const score = value ?? 0;

    const getScoreClass = () => {
        if (score >= 80) return "score-good";
        if (score >= 50) return "score-mid";
        return "score-bad";
    };

    return (
        <div className="score-card">

            <div>
                <span className="score-card-title">
                    {title}
                </span>

                {subtitle && (
                    <span className="score-card-subtitle">
                        {subtitle}
                    </span>
                )}
            </div>

            <strong
                className={getScoreClass()}
            >
                {score}
            </strong>

        </div>
    );
}


/* =========================
   METRIC
========================= */

function Metric({
    label,
    value
}) {

    return (
        <div className="metric">

            <span className="metric-label">
                {label}
            </span>

            <strong>
                {value || "—"}
            </strong>

        </div>
    );
}


/* =========================
   CHECK
========================= */

function Check({
    label,
    value
}) {

    return (
        <div className="check">

            <div>
                <span className="check-label">
                    {label}
                </span>
            </div>

            <span
                className={
                    value
                        ? "check-ok"
                        : "check-fail"
                }
            >
                {value ? "✓ OK" : "✕ Problème"}
            </span>

        </div>
    );
}
