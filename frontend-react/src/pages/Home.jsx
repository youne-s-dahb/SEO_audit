import { useAuth } from "../components/AuthContext";

export default function Home() {
  const { user } = useAuth();

  return (
    <div className="home-page">
      <div className="home-card">
        <span className="eyebrow">Dashboard</span>
        <h1>Marhba, {user?.name} 👋</h1>
        <p>Nta dakhel b l'email: {user?.email}</p>
      </div>
    </div>
  );
}
