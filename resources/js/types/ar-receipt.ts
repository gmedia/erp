import { BaseEntity, SimpleEntity } from './entity';

export type ArReceiptStatus =
    | 'draft'
    | 'confirmed'
    | 'reconciled'
    | 'cancelled'
    | 'void';

export type ArReceiptPaymentMethod =
    | 'bank_transfer'
    | 'cash'
    | 'check'
    | 'giro'
    | 'credit_card'
    | 'other';

export interface ArReceiptAllocation {
    id: number;
    customer_invoice_id: number;
    invoice_number: string;
    allocated_amount: string;
    discount_given: string;
    notes?: string | null;
}

export interface ArReceipt extends BaseEntity {
    receipt_number: string | null;
    customer: SimpleEntity | null;
    branch: SimpleEntity | null;
    fiscal_year: SimpleEntity | null;
    receipt_date: string;
    payment_method: ArReceiptPaymentMethod;
    bank_account: SimpleEntity | null;
    currency: string;
    total_amount: string;
    total_allocated: string;
    total_unallocated: string;
    reference: string | null;
    status: ArReceiptStatus;
    notes?: string | null;
    journal_entry_id: number | null;
    created_by: SimpleEntity | null;
    confirmed_by: SimpleEntity | null;
    confirmed_at: string | null;
    allocations?: ArReceiptAllocation[];
}

export interface ArReceiptFormData {
    customer_id: string;
    branch_id: string;
    fiscal_year_id: string;
    receipt_date: Date;
    payment_method: ArReceiptPaymentMethod;
    bank_account_id?: string;
    currency: string;
    total_amount: number;
    reference?: string;
    status: ArReceiptStatus;
    notes?: string;
    allocations: Array<{
        customer_invoice_id: string;
        invoice_label?: string;
        allocated_amount: number;
        discount_given?: number;
        notes?: string;
    }>;
}

export interface ArReceiptFilters {
    search: string;
    customer_id: string;
    branch_id: string;
    status: string;
    payment_method: string;
    currency: string;
    receipt_date_from: string;
    receipt_date_to: string;
}
