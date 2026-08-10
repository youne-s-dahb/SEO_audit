import { Link, useNavigate } from "react-router-dom";
import { useAuth } from "./AuthContext";

export default function Navbar() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  function handleLogout() {
    logout();
    navigate("/login");
  }

  return (
    <nav className="navbar">
      <Link to="/" className="navbar-brand">
        Marhba<span className="dot">.</span>
      </Link>
      <div className="navbar-links">
        {user ? (
          <>
            <span className="navbar-user">Ahlan, {user.name}</span>
            <button className="btn btn-ghost" onClick={handleLogout}>
              Kharej
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
    </nav>
  );
}
