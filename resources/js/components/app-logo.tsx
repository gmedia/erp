import AppLogoIcon from './app-logo-icon';
import { usePage } from '@inertiajs/react';
import { SharedData } from '@/types';

export default function AppLogo() {
    const { companyName } = usePage<SharedData>().props;
    
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-transparent dark:bg-sidebar-primary text-sidebar-primary-foreground">
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
