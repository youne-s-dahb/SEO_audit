import { useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import { useAuth } from "./AuthContext";

export default function RegisterForm() {
  const { register } = useAuth();
  const navigate = useNavigate();
  const [form, setForm] = useState({
    name: "",
    email: "",
    password: "",
    confirm: "",
  });
  const [error, setError] = useState("");

  function handleChange(e) {
    setForm({ ...form, [e.target.name]: e.target.value });
  }

  function handleSubmit(e) {
    e.preventDefault();
    setError("");

    if (!form.name || !form.email || !form.password || !form.confirm) {
      setError("3afak 3mmer ga3 les champs.");
      return;
    }
    if (form.password.length < 6) {
      setError("Password khass ykon 6 caractères o ktar.");
      return;
    }
    if (form.password !== form.confirm) {
      setError("Password machi mtabek m3a confirmation.");
      return;
    }

    const result = register(form);
    if (!result.ok) {
      setError(result.error);
      return;
    }
    navigate("/");
  }

  return (
    <form className="auth-form" onSubmit={handleSubmit} noValidate>
      <div className="field">
        <label htmlFor="name">Smiya</label>
        <input
          id="name"
          name="name"
          type="text"
          placeholder="Smiytek"
          value={form.name}
          onChange={handleChange}
          autoComplete="name"
        />
      </div>

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
        />
      </div>

      <div className="field">
        <label htmlFor="password">Password</label>
        <input
          id="password"
          name="password"
          type="password"
          placeholder="••••••••"
          value={form.password}
          onChange={handleChange}
          autoComplete="new-password"
        />
      </div>

      <div className="field">
        <label htmlFor="confirm">Confirmer Password</label>
        <input
          id="confirm"
          name="confirm"
          type="password"
          placeholder="••••••••"
          value={form.confirm}
          onChange={handleChange}
          autoComplete="new-password"
        />
      </div>

      {error && <p className="form-error">{error}</p>}

      <button type="submit" className="btn btn-primary btn-block">
        Créer compte
      </button>

      <p className="form-hint">
         3andek deja compte? <Link to="/login">Dkhol</Link>
      </p>
    </form>
  );
}
