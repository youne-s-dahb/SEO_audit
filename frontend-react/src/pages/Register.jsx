import RegisterForm from "../components/RegisterForm";

export default function Register() {
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
