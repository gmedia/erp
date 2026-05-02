import { BaseEntity, SimpleEntity } from './entity';

export type CreditNoteStatus =
    | 'draft'
    | 'confirmed'
    | 'applied'
    | 'cancelled'
    | 'void';

export type CreditNoteReason =
    | 'return'
    | 'discount'
    | 'correction'
    | 'bad_debt'
    | 'other';

export interface CreditNoteItem {
    id: number;
    product_id: number | null;
    product_name: string | null;
    account_id: number;
    account_name: string;
    description: string;
    quantity: string;
    unit_price: string;
    tax_percent: string;
    line_total: string;
    notes?: string | null;
}

export interface CreditNote extends BaseEntity {
    credit_note_number: string | null;
    customer: SimpleEntity | null;
    customer_invoice: { id: number; invoice_number: string } | null;
    branch: SimpleEntity | null;
    fiscal_year: SimpleEntity | null;
    credit_note_date: string;
    reason: CreditNoteReason;
    subtotal: string;
    tax_amount: string;
    grand_total: string;
    status: CreditNoteStatus;
    notes?: string | null;
    journal_entry_id: number | null;
    created_by: SimpleEntity | null;
    confirmed_by: SimpleEntity | null;
    confirmed_at: string | null;
    items?: CreditNoteItem[];
}

export interface CreditNoteFormData {
    customer_id: string;
    customer_invoice_id?: string;
    branch_id: string;
    fiscal_year_id: string;
    credit_note_date: Date;
    reason: CreditNoteReason;
    status: CreditNoteStatus;
    notes?: string;
    items: Array<{
        product_id?: string;
        product_label?: string;
        account_id: string;
        account_label?: string;
        description: string;
        quantity: number;
        unit_price: number;
        tax_percent?: number;
        notes?: string;
    }>;
}

export interface CreditNoteFilters {
    search: string;
    customer_id: string;
    branch_id: string;
    reason: string;
    status: string;
    credit_note_date_from: string;
    credit_note_date_to: string;
}