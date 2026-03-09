import { useAuth } from '@/contexts/auth-context';
import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    const { companyName } = useAuth();

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-transparent text-sidebar-primary-foreground">
                <AppLogoIcon className="size-5 fill-current" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {companyName || 'App'}
                </span>
            </div>
        </>
    );
}
