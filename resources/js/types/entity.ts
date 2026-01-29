// Base entity interfaces with stricter typing
export interface BaseEntity {
    readonly id: number;
    readonly created_at: string;
    readonly updated_at: string;
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

// Common entity patterns
export type EntityWithId<T = Record<string, unknown>> = T & BaseEntity;
export type EntityFormData<T = Record<string, unknown>> = T;
export type EntityFilters<T = Record<string, unknown>> = T & {
    search?: string;
};

// Re-export entity types for convenience
export type { Customer, CustomerFormData } from './customer';
export type { Department, DepartmentFormData } from './department';
export type { Employee, EmployeeFormData } from './employee';
export type { Position, PositionFormData } from './position';
export type { Supplier, SupplierFormData, SupplierFilters } from './supplier';
export type { Product, ProductFormData } from './product';
