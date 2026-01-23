import { BaseEntity } from './entity';

export interface Customer extends BaseEntity {
    name: string;
    email: string;
    phone: string | null;
    address: string;
    branch: { id: number; name: string } | string;
    customer_type: 'individual' | 'company';
    status: 'active' | 'inactive';
    notes: string | null;
}

export interface CustomerFormData {
    name: string;
    email: string;
    phone: string;
    address: string;
    branch: string;
    customer_type: 'individual' | 'company';
    status: 'active' | 'inactive';
    notes: string;
}
