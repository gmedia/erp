// Shared entity types and interfaces
export interface BaseEntity {
    id: number;
    created_at: string;
    updated_at: string;
}

export interface SimpleEntity extends BaseEntity {
    name: string;
}

export interface SimpleEntityFormData {
    name: string;
}

export interface SimpleEntityFilters {
    search: string;
}

// Re-export from specific modules for convenience
export type { Department, DepartmentFormData } from './department';
export type { Position, PositionFormData } from './position';
export type { Employee, EmployeeFormData } from './employee';
