export * from './employee';
import type { UrlMethodPair } from '@inertiajs/react';
export * from './position';
export * from './user';

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};
export type BreadcrumbItem = {
    title: string;
    href?: string;
};

export type NavItem = {
    title: string;
    // href can be a plain string or an Inertia UrlMethodPair (e.g., route definition)
    href: string | UrlMethodPair;
    icon: React.ComponentType<React.SVGProps<SVGSVGElement>>;
};

export type SharedData = {
    auth: {
        // User data may include an optional avatar URL
        user: User & { avatar?: string };
    };
    // Add any additional shared properties here if needed
};
