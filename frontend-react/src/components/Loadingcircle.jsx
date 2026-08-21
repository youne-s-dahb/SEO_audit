/*
|--------------------------------------------------------------------------
| LOADING CIRCLE
|--------------------------------------------------------------------------
|
| Kompennent mochtarak: kaykhdem b nafss l'UI (circle progress +
| texte li kayet3addel selon l'avancement) f Home.jsx (recent reports)
| o AuditDetail.jsx (chargement audit).
|
| Kayosta3mel nafss les classes CSS li kanou déja f Home.css
| (home-recent-progress-circle, etc.) bach ma nzidouch CSS jdida.
|
*/

export default function LoadingCircle({
    progress = 0,
    title,
    subtitle = "Merci de patienter quelques instants.",
    messages,
}) {

    /*
     * Si "messages" ma tsalch, kanbniw
     * text par défaut selon l'avancement.
     */

    const defaultLabel = () => {

        if (progress < 30) {
            return "Connexion au serveur...";
        }

        if (progress < 60) {
            return "Récupération des données...";
        }

        if (progress < 90) {
            return "Préparation du résultat...";
        }

        if (progress < 100) {
            return "Presque terminé...";
        }

        return "Terminé !";
    };

    const label =
        title ||
        (messages
            ? messages(progress)
            : defaultLabel());


    return (

        <div className="home-recent-loading-card">

            <div className="home-recent-loading-content">


                {/* CIRCLE */}

                <div
                    className="home-recent-progress-circle"
                    style={{
                        "--progress":
                            `${Math.min(progress, 100) * 3.6}deg`,
                    }}
                >

                    <div className="home-recent-progress-inner">

                        <strong>
                            {Math.min(progress, 100)}%
                        </strong>

                        <span>
                            Chargement
                        </span>

                    </div>

                </div>


                {/* TEXT */}

                <div className="home-recent-loading-text">

                    <h3>
                        {label}
                    </h3>

                    <p>
                        {subtitle}
                    </p>

                    <div className="home-recent-loading-dots">
                        <span />
                        <span />
                        <span />
                    </div>

                </div>

            </div>

        </div>

    );
}
