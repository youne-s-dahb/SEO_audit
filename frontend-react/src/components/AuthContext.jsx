import { createContext, useContext, useEffect, useState } from "react";

const AuthContext = createContext(null);

const SESSION_KEY = "app_session";
const TOKEN_KEY = "token";

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  // Restore session after refresh
  useEffect(() => {
    try {
      const token = localStorage.getItem(TOKEN_KEY);
      const savedSession = localStorage.getItem(SESSION_KEY);

      console.log("RESTORE TOKEN:", token);
      console.log("RESTORE SESSION:", savedSession);

      if (token && savedSession) {
        const session = JSON.parse(savedSession);

        setUser(session);

        console.log("SESSION RESTORED:", session);
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
      const response = await fetch(
        "http://localhost:8000/api/login_check",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: JSON.stringify({
            email,
            password,
          }),
        }
      );

      const data = await response.json();

      console.log("LOGIN STATUS:", response.status);
      console.log("LOGIN DATA:", data);

      if (!response.ok) {
        return {
          ok: false,
          error:
            data.message ||
            "Email wla password machi sahih.",
        };
      }

      // Check JWT
      if (!data.token) {
        return {
          ok: false,
          error: "Backend ma rje3ch JWT token.",
        };
      }

      // Save token
      localStorage.setItem(TOKEN_KEY, data.token);

      // Save user session
      const session = {
        email: email,
      };

      localStorage.setItem(
        SESSION_KEY,
        JSON.stringify(session)
      );

      // Update React state
      setUser(session);

      console.log("TOKEN SAVED:", data.token);
      console.log("USER SAVED:", session);

      return {
        ok: true,
      };
    } catch (error) {
      console.error("LOGIN ERROR:", error);

      return {
        ok: false,
        error: "Ma9drnach نتاصلو بالـ backend.",
      };
    }
  }

  function logout() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(SESSION_KEY);

    setUser(null);

    console.log("LOGOUT DONE");
  }

  return (
    <AuthContext.Provider
      value={{
        user,
        loading,
        login,
        logout,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);

  if (!context) {
    throw new Error(
      "useAuth khass ykon dakhel AuthProvider"
    );
  }

  return context;
}