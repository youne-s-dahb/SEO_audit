import { useEffect, useState } from "react";
import { useParams, Link } from "react-router-dom";
import { useAuth } from "../components/AuthContext";
import AuditResult from "../components/AuditResult";

export default function AuditDetail() {
  const { id } = useParams();
  const { getAuditDetail } = useAuth();
  const [audit, setAudit] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    load();
  }, [id]);

  async function load() {
    setIsLoading(true);
    setError("");
    const result = await getAuditDetail(id);
    if (!result.ok) {
      setError(result.error);
    } else {
      setAudit(result.audit);
    }
    setIsLoading(false);
  }

  return (
    <div className="dashboard">
      <Link to="/history" className="link-small">
        ← Rje3 l Historique
      </Link>

      {isLoading ? (
        <p className="page-loading">Kaytloaded...</p>
      ) : error || !audit ? (
        <p className="form-error">{error || "Audit machi mawjoud."}</p>
      ) : (
        <AuditResult audit={audit} />
      )}
    </div>
  );
}