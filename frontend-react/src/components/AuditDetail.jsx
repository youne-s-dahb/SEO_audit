import { useEffect, useState } from "react";
import { useParams, Link } from "react-router-dom";
import { useAuth } from "../components/AuthContext";
import AuditResult from "../components/AuditResult";
import LoadingCircle from "../components/LoadingCircle";

export default function AuditDetail() {

    const { id } = useParams();

    const { getAuditDetail } = useAuth();

    const [audit, setAudit] = useState(null);

    const [isLoading, setIsLoading] = useState(true);

    const [loadingProgress, setLoadingProgress] = useState(0);

    const [error, setError] = useState("");


    useEffect(() => {

        load();

    }, [id]);


    async function load() {

        setIsLoading(true);

        setError("");

        setLoadingProgress(0);


        let progress = 0;

        /*
         * Nafss l'animation dyal progress
         * li kayna f Home.jsx (loadRecent):
         * 0 -> 90% b façon progressive,
         * 100% ghir mnin iji jawab l backend.
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

            const result = await getAuditDetail(id);

            clearInterval(progressInterval);

            if (!result.ok) {

                setError(
                    result.error ||
                    "Impossible de charger cet audit."
                );

                setAudit(null);

            } else {

                setLoadingProgress(100);

                /*
                 * Kankhliw 100% bayn 500ms,
                 * bhal f Home.jsx.
                 */

                await new Promise((resolve) =>
                    setTimeout(resolve, 500)
                );

                setAudit(result.audit);
            }

        } catch (err) {

            console.error("AUDIT DETAIL ERROR:", err);

            clearInterval(progressInterval);

            setError(
                "Une erreur est survenue lors du chargement de l'audit."
            );

            setAudit(null);

        } finally {

            clearInterval(progressInterval);

            setIsLoading(false);
        }
    }


    return (

        <div className="dashboard">

            <Link to="/history" className="link-small">
                ← Retour à l'historique
            </Link>


            {isLoading ? (

                <LoadingCircle
                    progress={loadingProgress}
                    subtitle="Nous récupérons les détails de cet audit."
                />

            ) : error || !audit ? (

                <div className="empty-state">

                    <p className="form-error">
                        {error || "Cet audit est introuvable."}
                    </p>

                    <Link to="/history" className="btn btn-primary">
                        Retour à l'historique
                    </Link>

                </div>

            ) : (

                <AuditResult audit={audit} />

            )}

        </div>
    );
}
