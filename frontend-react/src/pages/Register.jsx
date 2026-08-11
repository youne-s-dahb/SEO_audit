import { Navigate } from "react-router-dom";
import RegisterForm from "../components/RegisterForm";
import { useAuth } from "../components/AuthContext";

export default function Register() {
  const { user, loading } = useAuth();

  if (loading) {
    return <div className="page-loading">loading...</div>;
  }

  // Ila deja connecté, ma ykhassoch ybqa f register — redirigih l Home
  if (user) {
    return <Navigate to="/" replace />;
  }

  return (
    <div className="auth-page">
      <div className="auth-card">
        <h1 className="auth-title">Créer compte</h1>
        <p className="auth-subtitle">Khass ghi diqiqa wahda bach tbda.</p>
        <RegisterForm />
      </div>
    </div>
  );
}
