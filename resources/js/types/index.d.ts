// Shared types for the application
export interface SimpleEntity {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

export interface SimpleEntityFormData {
    name: string;
}

export interface SimpleEntityFilters {
    search: string;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: React.ComponentType<any> | null;
}

// Re-export User type for convenience
export type { User } from './user';

export interface SharedData {
    auth: {
        user: import('./user').User;
    };
    [key: string]: any;
}
