import { useEffect, useState } from "react";
import { useSearchParams, Link } from "react-router-dom";

export default function VerifyEmail() {
  const [searchParams] = useSearchParams();
  const token = searchParams.get("token");
  const [status, setStatus] = useState("loading"); // loading | success | error
  const [message, setMessage] = useState("");

  useEffect(() => {
    if (!token) {
      setStatus("error");
      setMessage("Token khassin f lien.");
      return;
    }

    fetch(`http://localhost:8000/api/verify-email?token=${token}`)
      .then(async (res) => {
        const data = await res.json();
        if (!res.ok) {
          throw new Error(data.message || "Chi mochkil sar.");
        }
        setStatus("success");
        setMessage(data.message || "Compte tconfirma b nja7.");
      })
      .catch((err) => {
        setStatus("error");
        setMessage(err.message);
      });
  }, [token]);

  return (
    <div className="home-page">
      <div className="home-card">
        {status === "loading" && <p>Kaytverifi l'compte...</p>}

        {status === "success" && (
          <>
            <span className="eyebrow">Mabrouk</span>
            <h1>{message}</h1>
            <p>
              <Link to="/login">Dkhol daba</Link>
            </p>
          </>
        )}

        {status === "error" && (
          <>
            <span className="eyebrow">Erreur</span>
            <h1>{message}</h1>
            <p>
              <Link to="/register">Rje3 t sjjel</Link>
            </p>
          </>
        )}
      </div>
    </div>
  );
}