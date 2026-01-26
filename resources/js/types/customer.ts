import { BaseEntity } from './entity';

export interface Customer extends BaseEntity {
    name: string;
    email: string;
    phone: string | null;
    address: string;
    branch: { id: number; name: string } | string;
    category: { id: number; name: string } | string;
    category_id: number | string;
    status: 'active' | 'inactive';
    notes: string | null;
}

export interface CustomerFormData {
    name: string;
    email: string;
    phone: string;
    address: string;
    branch: string;
    category_id: string;
    status: 'active' | 'inactive';
    notes: string;
}
