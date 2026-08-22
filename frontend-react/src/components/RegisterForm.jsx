import { useState } from "react";
import { Link,useNavigate } from "react-router-dom";
import { useAuth } from "./AuthContext";

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export default function RegisterForm() {
  const { register, sendVerificationCode, verifyEmailCode } = useAuth();
  const navigate = useNavigate();
  const [step, setStep] = useState(1); // 1 = email, 2 = code, 3 = smiya + password

  const [form, setForm] = useState({
    email: "",
    code: "",
    name: "",
    password: "",
    confirm: "",
  });
  const [error, setError] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [successMessage, setSuccessMessage] = useState("");

  function handleChange(e) {
    setForm({ ...form, [e.target.name]: e.target.value });
  }

  function handleCodeChange(e) {
    const digitsOnly = e.target.value.replace(/\D/g, "").slice(0, 6);
    setForm({ ...form, code: digitsOnly });
  }

  async function handleSendCode(e) {
    e.preventDefault();
    setError("");

    if (!form.email) {
      setError("Veuillez saisir votre email.");
      return;
    }
    if (!EMAIL_REGEX.test(form.email)) {
      setError("Email non valide.");
      return;
    }

    setIsLoading(true);
    try {
      const result = await sendVerificationCode(form.email);
      if (!result.ok) {
        setError(result.error);
        return;
      }
      setStep(2);
    } finally {
      setIsLoading(false);
    }
  }

  async function handleVerifyCode(e) {
    e.preventDefault();
    setError("");

    if (form.code.length !== 6) {
      setError("Le code doit contenir au moins 6 caractères.");
      return;
    }

    setIsLoading(true);
    try {
      const result = await verifyEmailCode(form.email, form.code);
      if (!result.ok) {
        setError(result.error);
        return;
      }
      setStep(3);
    } finally {
      setIsLoading(false);
    }
  }

  async function handleResendCode() {
    setError("");
    setIsLoading(true);
    try {
      const result = await sendVerificationCode(form.email);
      if (!result.ok) {
        setError(result.error);
      }
    } finally {
      setIsLoading(false);
    }
  }

  function handleBackToEmail() {
    setError("");
    setForm({ ...form, code: "" });
    setStep(1);
  }

  async function handleSubmit(e) {
  e.preventDefault();
  setError("");

  if (!form.name || !form.password || !form.confirm) {
    setError("Veuillez remplir tous les champs.");
    return;
  }
  if (form.password.length < 6) {
    setError("Le mot de passe doit contenir au moins 6 caractères.");
    return;
  }
  if (form.password !== form.confirm) {
    setError("Les mots de passe ne correspondent pas.");
    return;
  }

  setIsLoading(true);
  try {
    const result = await register(form.name, form.email, form.password);
    if (!result.ok) {
      setError(result.error);
      return;
    }
    // Compte tcreah o l'utilisateur connecté automatiquement — dekhelo l Home direct
    navigate("/");
  } finally {
    setIsLoading(false);
  }
}

  if (successMessage) {
    return (
      <div className="form-success">
        <p>{successMessage}</p>
        <p className="form-hint">
          <Link to="/login">Se connecter</Link>
        </p>
      </div>
    );
  }

  return (
    <>
      <div className="step-indicator">
        <span className={`step-dot ${step >= 1 ? "active" : ""}`}>1</span>
        <span className="step-line" />
        <span className={`step-dot ${step >= 2 ? "active" : ""}`}>2</span>
        <span className="step-line" />
        <span className={`step-dot ${step >= 3 ? "active" : ""}`}>3</span>
      </div>

      {step === 1 && (
        <form className="auth-form" onSubmit={handleSendCode} noValidate>
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
              autoFocus
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
                Envoi du code en cours…
              </>
            ) : (
              "Envoyer le code"
            )}
          </button>

          <p className="form-hint">
            Vous avez déjà un compte? <Link to="/login">Se connecter</Link>
          </p>
        </form>
      )}

      {step === 2 && (
        <form className="auth-form" onSubmit={handleVerifyCode} noValidate>
          <p className="otp-hint">
            Un code de vérification a été envoyé à <strong>{form.email}</strong>. Veuillez le saisir ci-dessous:
          </p>

          <div className="field">
            <label htmlFor="code">Code (6 chiffres)</label>
            <input
              id="code"
              name="code"
              type="text"
              inputMode="numeric"
              pattern="\d*"
              maxLength={6}
              placeholder="000000"
              value={form.code}
              onChange={handleCodeChange}
              className="otp-input"
              disabled={isLoading}
              autoFocus
            />
          </div>

          {error && <p className="form-error">{error}</p>}

          <div className="form-row">
            <button
              type="button"
              className="btn btn-ghost"
              onClick={handleBackToEmail}
              disabled={isLoading}
            >
              Retour
            </button>
            <button
              type="submit"
              className="btn btn-primary btn-block"
              disabled={isLoading || form.code.length !== 6}
              aria-busy={isLoading}
            >
              {isLoading ? (
                <>
                  <span className="spinner" aria-hidden="true" />
                  Vérification en cours…
                </>
              ) : (
                "Confirmer code"
              )}
            </button>
          </div>

          <p className="form-hint">
            Vous n'avez pas reçu de code?{" "}
            <button
              type="button"
              className="link-button"
              onClick={handleResendCode}
              disabled={isLoading}
            >
              Renvoyer le code
            </button>
          </p>
        </form>
      )}

      {step === 3 && (
        <form className="auth-form" onSubmit={handleSubmit} noValidate>
          <div className="field field-readonly">
            <label>Email</label>
            <div className="field-static">
              {form.email}
              <span className="field-verified">✓ Confirmé</span>
            </div>
          </div>

          <div className="field">
            <label htmlFor="name">Nom</label>
            <input
              id="name"
              name="name"
              type="text"
              placeholder="Smiytek"
              value={form.name}
              onChange={handleChange}
              autoComplete="name"
              disabled={isLoading}
              autoFocus
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
              autoComplete="new-password"
              disabled={isLoading}
            />
          </div>

          <div className="field">
            <label htmlFor="confirm">Confirmer le mot de passe</label>
            <input
              id="confirm"
              name="confirm"
              type="password"
              placeholder="••••••••"
              value={form.confirm}
              onChange={handleChange}
              autoComplete="new-password"
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
                Création du compte en cours…
              </>
            ) : (
              "Créer un compte"
            )}
          </button>
        </form>
      )}
    </>
  );
}