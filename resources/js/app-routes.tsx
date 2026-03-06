import { Routes, Route, Navigate } from 'react-router-dom';
import ProtectedRoute from './components/protected-route';
import GuestRoute from './components/guest-route';
import Dashboard from './pages/dashboard';
import Login from './pages/auth/login';

export default function AppRoutes() {
    return (
        <Routes>
            {/* Guest-only routes */}
            <Route path="/login" element={<GuestRoute><Login /></GuestRoute>} />

            {/* Protected routes */}
            <Route path="/dashboard" element={<ProtectedRoute><Dashboard /></ProtectedRoute>} />

            {/* Root redirect */}
            <Route path="/" element={<Navigate to="/dashboard" replace />} />

            {/* Fallback - redirect to dashboard (auth guard will handle redirect to login) */}
            <Route path="*" element={<Navigate to="/dashboard" replace />} />
        </Routes>
    );
}
