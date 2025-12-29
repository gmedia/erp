// Base entity interfaces
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

// Re-export entity types for convenience
export type { Department, DepartmentFormData } from './department';
export type { Employee, EmployeeFormData } from './employee';
export type { Position, PositionFormData } from './position';
