import { Navigate } from "react-router-dom";
import RegisterForm from "../components/RegisterForm";
import { useAuth } from "../components/AuthContext";

export default function Register() {
  const { user, loading } = useAuth();

  if (loading) {
    return <div className="page-loading">Chargement...</div>;
  }

  // Ila deja connecté, ma ykhassoch ybqa f register — redirigih l Home
  if (user) {
    return <Navigate to="/" replace />;
  }

  return (
    <div className="auth-page">
      <div className="auth-card">
        <h1 className="auth-title">Créer un compte</h1>
        <p className="auth-subtitle">
          Quelques instants suffisent pour commencer.
        </p>
        <RegisterForm />
      </div>
    </div>
  );
}