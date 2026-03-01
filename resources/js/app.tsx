import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { router } from '@inertiajs/react';
import { initializeTheme } from './hooks/use-appearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const queryClient = new QueryClient();

import { Toaster } from 'sonner';

// Get company name from window object set by Blade
const getCompanyName = (): string => {
    return (window as any).__APP_COMPANY_NAME__ || appName;
};

createInertiaApp({
    title: (title) => {
        const companyName = getCompanyName();
        return title ? `${title} - ${companyName}` : companyName;
    },
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        // Update company name from props on page visit
        const propsCompanyName = (props.initialPage.props as any).companyName;
        if (propsCompanyName) {
            (window as any).__APP_COMPANY_NAME__ = propsCompanyName;
        }
        
        // Update on every subsequent page finish
        router.on('finish', (event) => {
            const newCompanyName = ((event as any).detail?.page?.props as any)?.companyName;
            if (newCompanyName) {
                (window as any).__APP_COMPANY_NAME__ = newCompanyName;
            }
        });
        
        const root = createRoot(el);

        root.render(
            <QueryClientProvider client={queryClient}>
                <App {...props} />
                <Toaster />
            </QueryClientProvider>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
