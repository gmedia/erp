import { Routes, Route } from 'react-router-dom';
import Dashboard from './pages/dashboard';
import Login from './pages/auth/login';

// Basic routing setup for the SPA
export default function AppRoutes() {
    return (
        <Routes>
            <Route path="/" element={<Dashboard />} />
            <Route path="/dashboard" element={<Dashboard />} />
            <Route path="/login" element={<Login />} />
            {/* Fallback route */}
            <Route path="*" element={<Dashboard />} />
        </Routes>
    );
}
