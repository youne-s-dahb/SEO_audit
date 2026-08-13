import { createContext, useContext, useEffect, useState } from "react";

const AuthContext = createContext(null);

const SESSION_KEY = "app_session";
const TOKEN_KEY = "token";
const API_URL = "http://localhost:8000/api";

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    // ==========================================
    // Restore session
    // ==========================================
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
            console.error("SESSION ERROR:", error);

            localStorage.removeItem(TOKEN_KEY);
            localStorage.removeItem(SESSION_KEY);

            setUser(null);
        } finally {
            setLoading(false);
        }
    }, []);

    // ==========================================
    // LOGIN
    // ==========================================
    async function login(email, password) {
        try {
            const response = await fetch(`${API_URL}/login_check`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify({
                    email,
                    password,
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                return {
                    ok: false,
                    error:
                        data.message ||
                        "Email wla password machi sahih.",
                };
            }

            if (!data.token) {
                return {
                    ok: false,
                    error: "Backend ma rje3ch JWT token.",
                };
            }

            // Save JWT
            localStorage.setItem(TOKEN_KEY, data.token);

            // Get current user
            let session = {
                email,
            };

            try {
                const meResponse = await fetch(`${API_URL}/me`, {
                    method: "GET",
                    headers: {
                        Authorization: `Bearer ${data.token}`,
                        Accept: "application/json",
                    },
                });

                if (meResponse.ok) {
                    const meData = await meResponse.json();

                    session = {
                        name: meData.full_name,
                        email: meData.email,
                    };
                }
            } catch (meError) {
                console.error("ME ERROR:", meError);
            }

            localStorage.setItem(
                SESSION_KEY,
                JSON.stringify(session)
            );

            setUser(session);

            return {
                ok: true,
                user: session,
            };
        } catch (error) {
            console.error("LOGIN ERROR:", error);

            return {
                ok: false,
                error: "Ma9drnach nettaslo b backend.",
            };
        }
    }

    // ==========================================
    // SEND VERIFICATION CODE
    // ==========================================
    async function sendVerificationCode(email) {
        try {
            const response = await fetch(
                `${API_URL}/register/send-code`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        email,
                    }),
                }
            );

            const data = await response.json();

            if (!response.ok) {
                return {
                    ok: false,
                    error:
                        data.message ||
                        "Ma9dertch nsift code.",
                };
            }

            return {
                ok: true,
                message: data.message,
            };
        } catch (error) {
            console.error("SEND CODE ERROR:", error);

            return {
                ok: false,
                error: "Ma9drnach nettaslo b backend.",
            };
        }
    }

    // ==========================================
    // VERIFY EMAIL CODE
    // ==========================================
    async function verifyEmailCode(email, code) {
        try {
            const response = await fetch(
                `${API_URL}/register/verify-code`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        email,
                        code,
                    }),
                }
            );

            const data = await response.json();

            if (!response.ok) {
                return {
                    ok: false,
                    error:
                        data.message ||
                        "Code machi sahih.",
                };
            }

            return {
                ok: true,
                message: data.message,
            };
        } catch (error) {
            console.error("VERIFY CODE ERROR:", error);

            return {
                ok: false,
                error: "Ma9drnach nettaslo b backend.",
            };
        }
    }

    // ==========================================
    // REGISTER
    // ==========================================
    async function register(fullName, email, password) {
        try {
            const response = await fetch(
                `${API_URL}/register`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        full_name: fullName,
                        email,
                        password,
                    }),
                }
            );

            const data = await response.json();

            if (!response.ok) {
                return {
                    ok: false,
                    error:
                        data.message ||
                        "Chi mochkil sar mnin dert register.",
                };
            }

            if (!data.token) {
                return {
                    ok: false,
                    error:
                        "Backend ma rje3ch JWT token.",
                };
            }

            localStorage.setItem(
                TOKEN_KEY,
                data.token
            );

            const session = {
                name: fullName,
                email,
            };

            localStorage.setItem(
                SESSION_KEY,
                JSON.stringify(session)
            );

            setUser(session);

            return {
                ok: true,
                message: data.message,
            };
        } catch (error) {
            console.error("REGISTER ERROR:", error);

            return {
                ok: false,
                error: "Ma9drnach nettaslo b backend.",
            };
        }
    }

    // ==========================================
    // LOGOUT
    // ==========================================
    function logout() {
        localStorage.removeItem(TOKEN_KEY);
        localStorage.removeItem(SESSION_KEY);

        setUser(null);
    }

    // ==========================================
    // AUTH HEADERS
    // ==========================================
    function authHeaders() {
        const token = localStorage.getItem(TOKEN_KEY);

        return {
            "Content-Type": "application/json",
            Accept: "application/json",

            ...(token
                ? {
                      Authorization: `Bearer ${token}`,
                  }
                : {}),
        };
    }

    // ==========================================
    // RUN AUDIT
    // ==========================================
    async function runAudit(url) {
    try {
        const response = await fetch(
            `${API_URL}/audits/run`,
            {
                method: "POST",
                headers: authHeaders(),
                body: JSON.stringify({ url }),
            }
        );

        const data = await response.json();

        console.log("AUDIT RESULT:", data);

        if (!response.ok) {
            return {
                ok: false,
                error:
                    data.error ||
                    data.message ||
                    "Ma9dertch ndir l'audit.",
            };
        }

        return {
            ok: true,
            audit: data,
        };

    } catch (error) {

        console.error(
            "AUDIT ERROR:",
            error
        );

        return {
            ok: false,
            error:
                "Ma9drnach nettaslo b backend.",
        };
    }
}

    // ==========================================
    // AUDIT HISTORY
    // ==========================================
    async function getAuditHistory() {
        try {
            const response = await fetch(
                `${API_URL}/audits`,
                {
                    method: "GET",
                    headers: authHeaders(),
                }
            );

            const data = await response.json();

            console.log("AUDIT HISTORY:", data);

            if (!response.ok) {
                return {
                    ok: false,
                    error:
                        data.message ||
                        data.error ||
                        "Ma9dertch njib l'historique.",
                };
            }

            const reports = (
                Array.isArray(data) ? data : []
            ).map((r) => ({
                id: r.id,

                // Backend peut retourner site comme IRI
                site: r.site,

                status: r.status,

                // IMPORTANT:
                // globalScore au lieu de score
                score: r.globalScore,

                // camelCase au lieu de snake_case
                scoreColor: r.scoreColor,

                createdAt: r.createdAt,

                pageLoadTimeMs:
                    r.pageLoadTimeMs,

                pagespeedDesktopScore:
                    r.pagespeedDesktopScore,

                pagespeedMobileScore:
                    r.pagespeedMobileScore,

                accessibilityScore:
                    r.accessibilityScore,

                bestPracticesScore:
                    r.bestPracticesScore,

                seoScore:
                    r.seoScore,

                mobileFriendly:
                    r.mobileFriendly,

                https:
                    r.https,

                robotsTxt:
                    r.robotsTxt,

                sitemapXml:
                    r.sitemapXml,
            }));

            return {
                ok: true,
                reports,
            };
        } catch (error) {
            console.error(
                "HISTORY ERROR:",
                error
            );

            return {
                ok: false,
                error:
                    "Ma9drnach nettaslo b backend.",
            };
        }
    }

    // ==========================================
    // AUDIT DETAIL
    // ==========================================
    async function getAuditDetail(id) {
        try {
            const response = await fetch(
                `${API_URL}/audits/${id}`,
                {
                    method: "GET",
                    headers: authHeaders(),
                }
            );

            const data = await response.json();

            console.log(
                "AUDIT DETAIL RESPONSE:",
                data
            );

            if (!response.ok) {
                return {
                    ok: false,
                    error:
                        data.message ||
                        data.error ||
                        "Ma9dertch njib had l'audit.",
                };
            }

            return {
                ok: true,
                audit: data,
            };
        } catch (error) {
            console.error(
                "AUDIT DETAIL ERROR:",
                error
            );

            return {
                ok: false,
                error:
                    "Ma9drnach nettaslo b backend.",
            };
        }
    }

    // ==========================================
    // CONTEXT
    // ==========================================
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
                getAuditDetail,
            }}
        >
            {children}
        </AuthContext.Provider>
    );
}

// ==========================================
// useAuth Hook
// ==========================================
export function useAuth() {
    const context = useContext(AuthContext);

    if (!context) {
        throw new Error(
            "useAuth khass ykon dakhel AuthProvider"
        );
    }

    return context;
}

