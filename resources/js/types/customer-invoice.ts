import { BaseEntity, SimpleEntity } from './entity';

export type CustomerInvoiceStatus =
    | 'draft'
    | 'sent'
    | 'partially_paid'
    | 'paid'
    | 'overdue'
    | 'cancelled'
    | 'void';

export interface CustomerInvoiceItem {
    id: number;
    product_id: number | null;
    product_name: string | null;
    account_id: number;
    account_name: string;
    description: string;
    quantity: string;
    unit_id: number | null;
    unit_name: string | null;
    unit_price: string;
    discount_percent: string;
    tax_percent: string;
    line_total: string;
    notes?: string | null;
}

export interface CustomerInvoice extends BaseEntity {
    invoice_number: string | null;
    customer: SimpleEntity | null;
    branch: SimpleEntity | null;
    fiscal_year: SimpleEntity | null;
    invoice_date: string;
    due_date: string;
    payment_terms: string | null;
    currency: string;
    subtotal: string;
    tax_amount: string;
    discount_amount: string;
    grand_total: string;
    amount_received: string;
    credit_note_amount: string;
    amount_due: string;
    status: CustomerInvoiceStatus;
    notes?: string | null;
    journal_entry_id: number | null;
    created_by: SimpleEntity | null;
    sent_by: SimpleEntity | null;
    sent_at: string | null;
    items?: CustomerInvoiceItem[];
}

export interface CustomerInvoiceFormData {
    customer_id: string;
    branch_id: string;
    fiscal_year_id: string;
    invoice_date: Date;
    due_date: Date;
    payment_terms?: string;
    currency: string;
    status: CustomerInvoiceStatus;
    notes?: string;
    items: Array<{
        product_id?: string;
        product_label?: string;
        account_id: string;
        account_label?: string;
        unit_id?: string;
        unit_label?: string;
        description: string;
        quantity: number;
        unit_price: number;
        discount_percent?: number;
        tax_percent?: number;
        notes?: string;
    }>;
}

export interface CustomerInvoiceFilters {
    search: string;
    customer_id: string;
    branch_id: string;
    status: string;
    currency: string;
    invoice_date_from: string;
    invoice_date_to: string;
    due_date_from: string;
    due_date_to: string;
}