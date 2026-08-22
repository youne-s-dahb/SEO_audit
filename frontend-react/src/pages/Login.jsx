import { Navigate } from "react-router-dom";
import LoginForm from "../components/LoginForm";
import { useAuth } from "../components/AuthContext";

export default function Login() {
  const { user, loading } = useAuth();

  if (loading) {
    return (
      <div className="page-loading">
        Chargement...
      </div>
    );
  }

  // إلا كان connecté، ممنوع عليه يبقى فـ login
  if (user) {
    return <Navigate to="/" replace />;
  }

  return (
    <div className="auth-page">
      <div className="auth-shell">
        <div className="auth-panel">
          <span className="auth-panel-eyebrow">01 — Bienvenue</span>
          <h2 className="auth-panel-title">
            Commencez votre
            <br />
            connexion en toute simplicité.
          </h2>
          <p className="auth-panel-text">
            Connectez-vous à votre compte et reprenez là où vous vous êtes
            arrêté. Votre session est conservée de manière sécurisée.
          </p>
          <div className="auth-panel-stats">
            <div>
              <strong>100%</strong>
              <span>Local &amp; privé</span>
            </div>
            <div>
              <strong>0s</strong>
              <span>Aucune configuration supplémentaire</span>
            </div>
          </div>
        </div>

        <div className="auth-card">
          <h1 className="auth-title">Bienvenue</h1>
          <p className="auth-subtitle">
            Connectez-vous à votre compte pour continuer.
          </p>
          <LoginForm />
        </div>
      </div>
    </div>
  );
}