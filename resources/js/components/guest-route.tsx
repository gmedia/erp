import { useAuth } from '@/contexts/auth-context';
import { LoaderCircle } from 'lucide-react';
import { Navigate } from 'react-router-dom';

type GuestRouteProps = Readonly<{
    children: React.ReactNode;
}>;

/**
 * Prevents authenticated users from accessing guest-only pages (login, register, etc.).
 * Shows spinner while auth is loading, then redirects to /dashboard if already logged in.
 */
export default function GuestRoute({ children }: GuestRouteProps) {
    const { user, isLoading } = useAuth();

    if (isLoading) {
        return (
            <div className="flex h-screen w-full items-center justify-center bg-background">
                <LoaderCircle className="h-8 w-8 animate-spin text-muted-foreground" />
            </div>
        );
    }

    if (user) {
        return <Navigate to="/dashboard" replace />;
    }

    return <>{children}</>;
}
