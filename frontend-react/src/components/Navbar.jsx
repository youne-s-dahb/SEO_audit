import { Link, useNavigate } from "react-router-dom";
import { useAuth } from "./AuthContext";

export default function Navbar() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  function handleLogout() {
    logout();
    navigate("/login");
  }

  const initial = (user?.name || user?.email || "?").trim().charAt(0).toUpperCase();

  return (
    <header className="navbar">
      <Link to="/" className="navbar-brand">
        SEO<span className="dot">Audit</span>
      </Link>

      {user && (
        <nav className="navbar-nav">
          <Link to="/" className="nav-link">
            Dashboard
          </Link>
          <Link to="/history" className="nav-link">
            Historique
          </Link>
          <Link to="/Keyword" className="nav-link">
            Keyword
          </Link>
           <Link to="/Keyword-Historique" className="nav-link">
            Keyword Historique
          </Link>
           <Link to="/Analyse" className="nav-link">
            Analyse Page
          </Link>
           <Link to="/Analyse-History" className="nav-link">
            Analyse Historique
          </Link>
        </nav>
      )}

      <div className="navbar-links">
        {user ? (
          <>
            <div className="user-menu">
              <span className="user-avatar">{initial}</span>
              <span className="navbar-user">{user.name || user.email}</span>
            </div>
            <button className="btn btn-ghost" onClick={handleLogout}>
              Deconnexion
            </button>
          </>
        ) : (
          <>
            <Link to="/login" className="btn btn-ghost">
              Login
            </Link>
            <Link to="/register" className="btn btn-primary">
              Créer compte
            </Link>
          </>
        )}
      </div>
    </header>
  );
}