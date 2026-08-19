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
      setError("3afak dkhel email dyalek.");
      return;
    }
    if (!EMAIL_REGEX.test(form.email)) {
      setError("Email machi valide.");
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
      setError("Code khass ykon 6 ar9am.");
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
          <Link to="/login">Dkhol daba</Link>
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
                Kayb3ath code...
              </>
            ) : (
              "B3ath code"
            )}
          </button>

          <p className="form-hint">
            3andek deja compte? <Link to="/login">Dkhol</Link>
          </p>
        </form>
      )}

      {step === 2 && (
        <form className="auth-form" onSubmit={handleVerifyCode} noValidate>
          <p className="otp-hint">
            Sifetna code l <strong>{form.email}</strong>. Dkhelo hna:
          </p>

          <div className="field">
            <label htmlFor="code">Code (6 ar9am)</label>
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
              Rje3
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
                  Kaytverifi...
                </>
              ) : (
                "Confirmi"
              )}
            </button>
          </div>

          <p className="form-hint">
            Ma jak walo?{" "}
            <button
              type="button"
              className="link-button"
              onClick={handleResendCode}
              disabled={isLoading}
            >
              Sift code mn jdid
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
              <span className="field-verified">✓ Mconfirmé</span>
            </div>
          </div>

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
              disabled={isLoading}
              autoFocus
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
              disabled={isLoading}
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
                Kaydir compte...
              </>
            ) : (
              "Créer compte"
            )}
          </button>
        </form>
      )}
    </>
  );
}