import { BaseEntity, SimpleEntity } from './entity';

export type ApPaymentStatus =
    | 'draft'
    | 'pending_approval'
    | 'confirmed'
    | 'reconciled'
    | 'cancelled'
    | 'void';

export type ApPaymentMethod =
    | 'bank_transfer'
    | 'cash'
    | 'check'
    | 'giro'
    | 'other';

export interface ApPaymentAllocation {
    id: number;
    supplier_bill_id: number;
    bill_number: string | null;
    allocated_amount: string;
    discount_taken: string;
    notes?: string | null;
}

export interface ApPayment extends BaseEntity {
    payment_number: string | null;
    supplier: SimpleEntity | null;
    branch: SimpleEntity | null;
    fiscal_year: SimpleEntity | null;
    payment_date: string;
    payment_method: ApPaymentMethod;
    bank_account: SimpleEntity | null;
    currency: string;
    total_amount: string;
    total_allocated: string;
    total_unallocated: string;
    reference: string | null;
    status: ApPaymentStatus;
    notes?: string | null;
    journal_entry_id: number | null;
    approved_by?: SimpleEntity | null;
    approved_at?: string | null;
    created_by?: SimpleEntity | null;
    confirmed_by?: SimpleEntity | null;
    confirmed_at?: string | null;
    allocations?: ApPaymentAllocation[];
}

export interface ApPaymentFormData {
    payment_number?: string;
    supplier_id: string;
    branch_id: string;
    fiscal_year_id: string;
    payment_date: Date;
    payment_method: ApPaymentMethod;
    bank_account_id: string;
    currency: string;
    total_amount: number;
    reference?: string;
    status: ApPaymentStatus;
    notes?: string;
    allocations: Array<{
        supplier_bill_id: string;
        bill_label?: string;
        allocated_amount: number;
        discount_taken?: number;
        notes?: string;
    }>;
}

export interface ApPaymentFilters {
    search: string;
    supplier_id: string;
    branch_id: string;
    status: string;
    payment_method: string;
    currency: string;
    payment_date_from: string;
    payment_date_to: string;
}
