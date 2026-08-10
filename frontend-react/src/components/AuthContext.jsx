import { createContext, useContext, useState, useEffect } from "react";

const AuthContext = createContext(null);

const USERS_KEY = "app_users";
const SESSION_KEY = "app_session";

function readUsers() {
  try {
    return JSON.parse(localStorage.getItem(USERS_KEY)) || [];
  } catch {
    return [];
  }
}

function writeUsers(users) {
  localStorage.setItem(USERS_KEY, JSON.stringify(users));
}

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const saved = localStorage.getItem(SESSION_KEY);
    if (saved) {
      try {
        setUser(JSON.parse(saved));
      } catch {
        localStorage.removeItem(SESSION_KEY);
      }
    }
    setLoading(false);
  }, []);

  function register({ name, email, password }) {
    const users = readUsers();
    const exists = users.some((u) => u.email.toLowerCase() === email.toLowerCase());
    if (exists) {
      return { ok: false, error: "Kayn deja compte b had email." };
    }
    const newUser = { id: crypto.randomUUID(), name, email, password };
    writeUsers([...users, newUser]);
    const session = { id: newUser.id, name: newUser.name, email: newUser.email };
    localStorage.setItem(SESSION_KEY, JSON.stringify(session));
    setUser(session);
    return { ok: true };
  }

  function login({ email, password }) {
    const users = readUsers();
    const found = users.find(
      (u) => u.email.toLowerCase() === email.toLowerCase() && u.password === password
    );
    if (!found) {
      return { ok: false, error: "Email wla password machi sahih." };
    }
    const session = { id: found.id, name: found.name, email: found.email };
    localStorage.setItem(SESSION_KEY, JSON.stringify(session));
    setUser(session);
    return { ok: true };
  }

  function logout() {
    localStorage.removeItem(SESSION_KEY);
    setUser(null);
  }

  return (
    <AuthContext.Provider value={{ user, loading, register, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth khass ykon dakhel AuthProvider");
  return ctx;
}
