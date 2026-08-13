import { Routes, Route } from "react-router-dom";

import Home from "../pages/Home";
import History from "../pages/History";
import Report from "../pages/Report";

import Login from "../pages/Login";
import Register from "../pages/Register";
import VerifyEmail from "../pages/VerifyEmail";
import NotFound from "../pages/NotFound";

import ProtectedRoute from "../components/ProtectedRoute";

export default function AppRoutes() {
    return (
        <Routes>

            {/* =========================
                HOME
            ========================= */}

            <Route
                path="/"
                element={
                    <ProtectedRoute>
                        <Home />
                    </ProtectedRoute>
                }
            />


            {/* =========================
                HISTORY
            ========================= */}

            <Route
                path="/history"
                element={
                    <ProtectedRoute>
                        <History />
                    </ProtectedRoute>
                }
            />


            {/* =========================
                REPORT
            ========================= */}

            <Route
                path="/audits/:id"
                element={
                    <ProtectedRoute>
                        <Report />
                    </ProtectedRoute>
                }
            />


            {/* =========================
                AUTH
            ========================= */}

            <Route
                path="/login"
                element={
                    <Login />
                }
            />

            <Route
                path="/register"
                element={
                    <Register />
                }
            />

            <Route
                path="/verify-email"
                element={
                    <VerifyEmail />
                }
            />


            {/* =========================
                NOT FOUND
            ========================= */}

            <Route
                path="*"
                element={
                    <NotFound />
                }
            />

        </Routes>
    );
}