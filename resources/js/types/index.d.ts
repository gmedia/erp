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
