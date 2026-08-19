import { Navigate } from "react-router-dom";
import LoginForm from "../components/LoginForm";
import { useAuth } from "../components/AuthContext";

export default function Login() {
  const { user, loading } = useAuth();
   if (loading) {
    return (
      <div className="page-loading">
        Kaytloaded...
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
            Kolla chi bda
            <br />
            b login wahed.
          </h2>
          <p className="auth-panel-text">
            Dkhol l compte dyalek o kml mnin wqft. Session dyalek mahfouda o
            aman.
          </p>
          <div className="auth-panel-stats">
            <div>
              <strong>100%</strong>
              <span>Local &amp; privé</span>
            </div>
            <div>
              <strong>0s</strong>
              <span>Setup zayd</span>
            </div>
          </div>
        </div>

        <div className="auth-card">
          <h1 className="auth-title">Ahlan bik</h1>
          <p className="auth-subtitle">Dkhol l compte dyalek bach tkml.</p>
          <LoginForm />
        </div>
      </div>
    </div>
  );
}
