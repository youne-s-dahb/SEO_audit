import { createContext, useContext, useEffect, useState } from "react";

const AuthContext = createContext(null);

const SESSION_KEY = "app_session";
const TOKEN_KEY = "token";
const API_URL = "http://localhost:8000/api";

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    try {
      const token = localStorage.getItem(TOKEN_KEY);
      const savedSession = localStorage.getItem(SESSION_KEY);

      if (token && savedSession) {
        setUser(JSON.parse(savedSession));
      } else {
        setUser(null);
      }
    } catch (error) {
      console.error("Session invalid:", error);
      localStorage.removeItem(TOKEN_KEY);
      localStorage.removeItem(SESSION_KEY);
      setUser(null);
    } finally {
      setLoading(false);
    }
  }, []);

  async function login(email, password) {
  try {
    const response = await fetch(`${API_URL}/login_check`, {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify({ email, password }),
    });

    const data = await response.json();

    if (!response.ok) {
      return { ok: false, error: data.message || "Email wla password machi sahih." };
    }
    if (!data.token) {
      return { ok: false, error: "Backend ma rje3ch JWT token." };
    }

    localStorage.setItem(TOKEN_KEY, data.token);

    // Njibo l'profil kaml (fih full_name) mn /api/me
    let session = { email };
    try {
      const meResponse = await fetch(`${API_URL}/me`, {
        headers: {
          Authorization: `Bearer ${data.token}`,
          Accept: "application/json",
        },
      });
      if (meResponse.ok) {
        const meData = await meResponse.json();
        session = { name: meData.full_name, email: meData.email };
      }
    } catch (meError) {
      console.error("ME ERROR:", meError);
    }

    localStorage.setItem(SESSION_KEY, JSON.stringify(session));
    setUser(session);

    return { ok: true };
  } catch (error) {
    console.error("LOGIN ERROR:", error);
    return { ok: false, error: "Ma9drnach nettaslo b backend." };
  }
}
  async function sendVerificationCode(email) {
    try {
      const response = await fetch(`${API_URL}/register/send-code`, {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({ email }),
      });

      const data = await response.json();

      if (!response.ok) {
        return { ok: false, error: data.message || "Ma9dertch nsift code." };
      }
      return { ok: true, message: data.message };
    } catch (error) {
      console.error("SEND CODE ERROR:", error);
      return { ok: false, error: "Ma9drnach nettaslo b backend." };
    }
  }

  async function verifyEmailCode(email, code) {
    try {
      const response = await fetch(`${API_URL}/register/verify-code`, {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({ email, code }),
      });

      const data = await response.json();

      if (!response.ok) {
        return { ok: false, error: data.message || "Code machi sahih." };
      }
      return { ok: true, message: data.message };
    } catch (error) {
      console.error("VERIFY CODE ERROR:", error);
      return { ok: false, error: "Ma9drnach nettaslo b backend." };
    }
  }

  async function register(fullName, email, password) {
  try {
    const response = await fetch(`${API_URL}/register`, {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify({ full_name: fullName, email, password }),
    });

    const data = await response.json();

    if (!response.ok) {
      return { ok: false, error: data.message || "Chi mochkil sar mnin dert register." };
    }
    if (!data.token) {
      return { ok: false, error: "Backend ma rje3ch JWT token." };
    }

    // Auto-login: khznu token o session direct, bla ma nredirigiw l login
    localStorage.setItem(TOKEN_KEY, data.token);
    const session = { name: fullName, email };
    localStorage.setItem(SESSION_KEY, JSON.stringify(session));
    setUser(session);

    return { ok: true, message: data.message };
  } catch (error) {
    console.error("REGISTER ERROR:", error);
    return { ok: false, error: "Ma9drnach nettaslo b backend." };
  }
}

  function logout() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(SESSION_KEY);
    setUser(null);
  }
  function authHeaders() {
  const token = localStorage.getItem(TOKEN_KEY);
  return {
    "Content-Type": "application/json",
    Accept: "application/json",
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
  };
}

async function runAudit(url) {
  try {
    const response = await fetch(`${API_URL}/audit/check`, {
      method: "POST",
      headers: authHeaders(),
      body: JSON.stringify({ url }),
    });

    const data = await response.json();

    if (!response.ok) {
      return { ok: false, error: data.message || "Ma9dertch ndir l'audit." };
    }
    return { ok: true, report: data };
  } catch (error) {
    console.error("AUDIT ERROR:", error);
    return { ok: false, error: "Ma9drnach nettaslo b backend." };
  }
}

async function getAuditHistory() {
  try {
    const response = await fetch(`${API_URL}/audit/history`, {
      method: "GET",
      headers: authHeaders(),
    });

    const data = await response.json();

    if (!response.ok) {
      return { ok: false, error: data.message || "Ma9dertch njib l'historique." };
    }
    return { ok: true, reports: Array.isArray(data) ? data : data.reports || [] };
  } catch (error) {
    console.error("HISTORY ERROR:", error);
    return { ok: false, error: "Ma9drnach nettaslo b backend." };
  }
}

return (
  <AuthContext.Provider
    value={{
      user,
      loading,
      login,
      register,
      sendVerificationCode,
      verifyEmailCode,
      logout,
      runAudit,
      getAuditHistory,
    }}
  >
    {children}
  </AuthContext.Provider>
);
  return (
    <AuthContext.Provider
      value={{ user, loading, login, register, sendVerificationCode, verifyEmailCode, logout }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error("useAuth khass ykon dakhel AuthProvider");
  }
  return context;
}