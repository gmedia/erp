import { useAuth } from '@/contexts/auth-context';
import { LoaderCircle } from 'lucide-react';
import { Navigate, useLocation } from 'react-router-dom';

/**
 * Shows a full-screen loading spinner while auth state is being determined,
 * then either renders children (if authenticated) or redirects to /login.
 */
export default function ProtectedRoute({
    children,
}: {
    children: React.ReactNode;
}) {
    const { user, isLoading } = useAuth();
    const location = useLocation();

    if (isLoading) {
        return (
            <div className="flex h-screen w-full items-center justify-center bg-background">
                <LoaderCircle className="h-8 w-8 animate-spin text-muted-foreground" />
            </div>
        );
    }

    if (!user) {
        // Save the attempted URL so we can redirect back after login
        return <Navigate to="/login" state={{ from: location }} replace />;
    }

    return <>{children}</>;
}
