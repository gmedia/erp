import { BaseEntity, SimpleEntity } from './entity';

export interface Supplier extends BaseEntity {
    name: string;
    email: string;
    phone: string | null;
    address: string;
    branch_id: number | null;
    branch: SimpleEntity | null | undefined;
    category_id: number | null;
    category: SimpleEntity | null | undefined;
    status: 'active' | 'inactive';
}

export interface SupplierFormData {
    name: string;
    email: string;
    phone: string;
    address: string;
    branch_id?: string; // AsyncSelect usually returns string ID
    category_id: string;
    status: 'active' | 'inactive';
}

export interface SupplierFilters {
    search: string;
    branch_id: string;
    category_id: string;
    status: string;
}
