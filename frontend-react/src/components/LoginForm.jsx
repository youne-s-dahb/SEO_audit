import { useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import { useAuth } from "./AuthContext";

export default function LoginForm() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const [form, setForm] = useState({ email: "", password: "" });
  const [error, setError] = useState("");
  const [isLoading, setIsLoading] = useState(false);

  function handleChange(e) {
    const { name, value } = e.target;
    setForm({ ...form, [name]: value });
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");

    if (!form.email || !form.password) {
      setError("Veuillez remplir tous les champs.");
      return;
    }

    setIsLoading(true);
    try {
      const result = await login(form.email, form.password);
      if (!result.ok) {
        setError(result.error);
        return;
      }
      navigate("/");
    } finally {
      setIsLoading(false);
    }
  }

  return (
    <form className="auth-form" onSubmit={handleSubmit} noValidate>
      <div className="field">
        <label htmlFor="email">Email</label>
        <input
          id="email"
          name="email"
          type="email"
          placeholder="ton@email.com"
          value={form.email}
          onChange={handleChange}
          autoComplete="email"
          disabled={isLoading}
        />
      </div>

      <div className="field">
        <label htmlFor="password">Mot de passe</label>
        <input
          id="password"
          name="password"
          type="password"
          placeholder="••••••••"
          value={form.password}
          onChange={handleChange}
          autoComplete="current-password"
          disabled={isLoading}
        />
      </div>

      {error && <p className="form-error">{error}</p>}

      <button
        type="submit"
        className="btn btn-primary btn-block"
        disabled={isLoading}
        aria-busy={isLoading}
      >
        {isLoading ? (
          <>
            <span className="spinner" aria-hidden="true" />
            Connexion...
          </>
        ) : (
          "Se connecter"
        )}
      </button>

      <p className="form-hint">
        Vous n'avez pas encore de compte? <Link to="/register">Créer un compte</Link>
      </p>
    </form>
  );
}