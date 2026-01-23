import { BaseEntity, SimpleEntity } from './entity';

export interface Supplier extends BaseEntity {
    name: string;
    email: string;
    phone: string | null;
    address: string;
    branch_id: number | null;
    branch: SimpleEntity | null | undefined;
    category: 'electronics' | 'furniture' | 'stationery' | 'services' | 'other';
    status: 'active' | 'inactive';
}

export interface SupplierFormData {
    name: string;
    email: string;
    phone: string;
    address: string;
    branch_id: string; // AsyncSelect usually returns string ID
    category: 'electronics' | 'furniture' | 'stationery' | 'services' | 'other';
    status: 'active' | 'inactive';
}

export interface SupplierFilters {
    search: string;
    branch_id: string;
    category: string;
    status: string;
}
