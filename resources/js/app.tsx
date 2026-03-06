import '../css/app.css';

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import { BrowserRouter } from 'react-router-dom';
import { HelmetProvider } from 'react-helmet-async';
import AppRoutes from './app-routes';
import { AuthProvider } from './contexts/auth-context';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const queryClient = new QueryClient();

import { Toaster } from 'sonner';

// Initialize light / dark mode on load
initializeTheme();

const rootElement = document.getElementById('app');
if (!rootElement) throw new Error('Failed to find the root element');

const root = createRoot(rootElement);

root.render(
    <HelmetProvider>
        <QueryClientProvider client={queryClient}>
            <BrowserRouter>
                <AppRoutes />
                <Toaster />
            </BrowserRouter>
        </QueryClientProvider>
    </HelmetProvider>
);
