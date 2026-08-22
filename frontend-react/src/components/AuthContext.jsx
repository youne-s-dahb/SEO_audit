import {
    createContext,
    useContext,
    useEffect,
    useState,
} from "react";

const AuthContext = createContext(null);

const SESSION_KEY = "app_session";
const TOKEN_KEY = "token";
const API_URL = "http://localhost:8000/api";

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    // ==========================================
    // RESTORE SESSION
    // ==========================================
    useEffect(() => {
        try {
            const token =
                localStorage.getItem(TOKEN_KEY);

            const savedSession =
                localStorage.getItem(SESSION_KEY);

            if (token && savedSession) {
                setUser(
                    JSON.parse(savedSession)
                );
            } else {
                setUser(null);
            }
        } catch (error) {
            console.error(
                "SESSION ERROR:",
                error
            );

            localStorage.removeItem(
                TOKEN_KEY
            );

            localStorage.removeItem(
                SESSION_KEY
            );

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
            const response = await fetch(
                `${API_URL}/login_check`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type":
                            "application/json",
                        Accept:
                            "application/json",
                    },
                    body: JSON.stringify({
                        email,
                        password,
                    }),
                }
            );

            const data =
                await response.json();

            if (!response.ok) {
                return {
                    ok: false,
                    error:
                        data.message ||
                        "Email ou mot de passe incorrect.",
                };
            }

            if (!data.token) {
                return {
                    ok: false,
                    error:
                        "Une erreur est survenue lors de l’authentification. Veuillez réessayer.",
                };
            }

            // Save JWT
            localStorage.setItem(
                TOKEN_KEY,
                data.token
            );

            // ==================================
            // GET CURRENT USER
            // ==================================

            let session = {
                email,
            };

            try {
                const meResponse =
                    await fetch(
                        `${API_URL}/me`,
                        {
                            method: "GET",
                            headers: {
                                Authorization:
                                    `Bearer ${data.token}`,
                                Accept:
                                    "application/json",
                            },
                        }
                    );

                if (meResponse.ok) {
                    const meData =
                        await meResponse.json();

                    session = {
                        name:
                            meData.full_name ||
                            meData.fullName ||
                            meData.name ||
                            email,

                        email:
                            meData.email ||
                            email,
                    };
                }
            } catch (meError) {
                console.error(
                    "ME ERROR:",
                    meError
                );
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
            console.error(
                "LOGIN ERROR:",
                error
            );

            return {
                ok: false,
                error:
                    "Impossible de se connecter au serveur. Veuillez réessayer dans quelques instants.",
            };
        }
    }

    // ==========================================
    // SEND VERIFICATION CODE
    // ==========================================
    async function sendVerificationCode(
        email
    ) {
        try {
            const response = await fetch(
                `${API_URL}/register/send-code`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type":
                            "application/json",
                        Accept:
                            "application/json",
                    },
                    body: JSON.stringify({
                        email,
                    }),
                }
            );

            const data =
                await response.json();

            if (!response.ok) {
                return {
                    ok: false,
                    error:
                        data.message ||
                        "Impossible d'envoyer le code de vérification. Veuillez réessayer dans quelques instants.",
                };
            }

            return {
                ok: true,
                message: data.message,
            };
        } catch (error) {
            console.error(
                "SEND CODE ERROR:",
                error
            );

            return {
                ok: false,
                error:
                    "Impossible de se connecter au serveur. Veuillez réessayer dans quelques instants.",
            };
        }
    }

    // ==========================================
    // VERIFY EMAIL CODE
    // ==========================================
    async function verifyEmailCode(
        email,
        code
    ) {
        try {
            const response = await fetch(
                `${API_URL}/register/verify-code`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type":
                            "application/json",
                        Accept:
                            "application/json",
                    },
                    body: JSON.stringify({
                        email,
                        code,
                    }),
                }
            );

            const data =
                await response.json();

            if (!response.ok) {
                return {
                    ok: false,
                    error:
                        data.message ||
                        "Code incorrect.",
                };
            }

            return {
                ok: true,
                message: data.message,
            };
        } catch (error) {
            console.error(
                "VERIFY CODE ERROR:",
                error
            );

            return {
                ok: false,
                error:
                    "Impossible de se connecter au serveur. Veuillez réessayer dans quelques instants.",
            };
        }
    }

    // ==========================================
    // REGISTER
    // ==========================================
    async function register(
        fullName,
        email,
        password
    ) {
        try {
            const response = await fetch(
                `${API_URL}/register`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type":
                            "application/json",
                        Accept:
                            "application/json",
                    },
                    body: JSON.stringify({
                        full_name: fullName,
                        email,
                        password,
                    }),
                }
            );

            const data =
                await response.json();

            if (!response.ok) {
                return {
                    ok: false,
                    error:
                        data.message ||
                        "Impossible de créer le compte. Veuillez réessayer.",
                };
            }

            if (!data.token) {
                return {
                    ok: false,
                    error:
                        "Impossible de se connecter au serveur. Veuillez réessayer dans quelques instants.",
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
            console.error(
                "REGISTER ERROR:",
                error
            );

            return {
                ok: false,
                error:
                    "Impossible de se connecter au serveur. Veuillez réessayer dans quelques instants.",
            };
        }
    }

    // ==========================================
    // LOGOUT
    // ==========================================
    function logout() {
        localStorage.removeItem(
            TOKEN_KEY
        );

        localStorage.removeItem(
            SESSION_KEY
        );

        setUser(null);
    }

    // ==========================================
    // AUTH HEADERS
    // ==========================================
    function authHeaders() {
        const token =
            localStorage.getItem(
                TOKEN_KEY
            );

        return {
            "Content-Type":
                "application/json",

            Accept:
                "application/json",

            ...(token
                ? {
                      Authorization:
                          `Bearer ${token}`,
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
                    headers:
                        authHeaders(),

                    body: JSON.stringify({
                        url,
                    }),
                }
            );

            const data =
                await response.json();

            console.log(
                "AUDIT RESULT:",
                data
            );

            if (!response.ok) {
                return {
                    ok: false,
                    error:
                        data.error ||
                        data.message ||
                        "Impossible de réaliser l’audit. Veuillez réessayer dans quelques instants.",
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
                    "Impossible de réaliser l’audit. Veuillez réessayer dans quelques instants.",
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
                    headers:
                        authHeaders(),
                }
            );

            const data =
                await response.json();

            console.log(
                "AUDIT HISTORY:",
                data
            );

            if (!response.ok) {
                return {
                    ok: false,
                    error:
                        data.message ||
                        data.error ||
                        "Impossible de récupérer l'historique.",
                };
            }

            /*
             * Backend dyalk kayrje3 array direct:
             *
             * [
             *   {
             *      id: 66,
             *      url: "...",
             *      status: "completed",
             *      score: 93,
             *      score_color: "green"
             *   }
             * ]
             */

            const audits =
                Array.isArray(data)
                    ? data
                    : [];

            const reports =
                audits.map((audit) => ({
                    id: audit.id ?? null,
                    url: audit.url ?? audit.siteUrl ?? null,
                    siteName:
                        audit.siteName ||
                        audit.site_name ||
                        null,
                    status: audit.status || null,
                    score:
                        audit.score ??
                        audit.globalScore ??
                        audit.global_score ??
                        0,
                    globalScore:
                        audit.globalScore ??
                        audit.global_score ??
                        audit.score ??
                        0,
                    scoreColor:
                        audit.scoreColor ||
                        audit.score_color ||
                        null,
                    createdAt:
                        audit.createdAt ||
                        audit.created_at ||
                        null,
                    requestedBy: audit.requestedBy || null,
                    userId: audit.userId ?? null,
                    pageLoadTimeMs:
                        audit.pageLoadTimeMs ??
                        audit.page_load_time_ms ??
                        null,
                    pagespeedDesktopScore:
                        audit.pagespeedDesktopScore ??
                        audit.pagespeed_desktop_score ??
                        audit.desktopScore ??
                        null,
                    pagespeedMobileScore:
                        audit.pagespeedMobileScore ??
                        audit.pagespeed_mobile_score ??
                        audit.mobileScore ??
                        null,
                    accessibilityScore:
                        audit.accessibilityScore ??
                        audit.accessibility_score ??
                        null,
                    bestPracticesScore:
                        audit.bestPracticesScore ??
                        audit.best_practices_score ??
                        null,
                    seoScore:
                        audit.seoScore ??
                        audit.seo_score ??
                        null,
                    mobileFriendly:
                        audit.mobileFriendly ??
                        audit.mobile_friendly ??
                        null,
                    https:
                        audit.https ?? null,
                    robotsTxt:
                        audit.robotsTxt ??
                        audit.robots_txt ??
                        null,
                    sitemapXml:
                        audit.sitemapXml ??
                        audit.sitemap_xml ??
                        null,
                    metrics: audit.metrics ?? {},
                    technicalSeo:
                        audit.technicalSeo ??
                        audit.technical_seo ??
                        null,
                    errorMessage:
                        audit.errorMessage ??
                        audit.error_message ??
                        null,
                    googleMap:
                        audit.googleMap ??
                        audit.google_map ??
                        audit.googleMaps ??
                        audit.google_maps ??
                        null,
                    googleMapsUrl:
                        audit.googleMapsUrl ??
                        audit.google_maps_url ??
                        null,
                    auditType:
                        audit.auditType ||
                        audit.audit_type ||
                        null,
                }));

            console.log(
                "FINAL AUDIT REPORTS:",
                reports
            );

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
                    "Impossible de récupérer l'historique. Veuillez réessayer dans quelques instants.",
            };
        }
    }

    // ==========================================
    // AUDIT DETAIL
    // ==========================================
    async function getAuditDetail(id) {
        try {
            const response =
                await fetch(
                    `${API_URL}/audits/${id}/report`,
                    {
                        method: "GET",
                        headers:
                            authHeaders(),
                    }
                );

            const data =
                await response.json();

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
                        "Impossible de récupérer les détails de l'audit.",
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
                    "Impossible de récupérer les détails de l'audit. Veuillez réessayer dans quelques instants.",
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
// USE AUTH HOOK
// ==========================================
export function useAuth() {
    const context =
        useContext(AuthContext);

    if (!context) {
        throw new Error(
            "useAuth doit être utilisé à l’intérieur de AuthProvider."
        );
    }

    return context;
}