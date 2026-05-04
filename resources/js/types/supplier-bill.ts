import { BaseEntity, SimpleEntity } from './entity';

export type SupplierBillStatus =
    | 'draft'
    | 'confirmed'
    | 'partially_paid'
    | 'paid'
    | 'overdue'
    | 'cancelled'
    | 'void';

export interface SupplierBillItem {
    id: number;
    product_id: number | null;
    product_name: string | null;
    account_id: number;
    account_name: string;
    description: string;
    quantity: string;
    unit_price: string;
    discount_percent: string;
    tax_percent: string;
    line_total: string;
    goods_receipt_item_id: number | null;
    notes?: string | null;
}

export interface SupplierBill extends BaseEntity {
    bill_number: string | null;
    supplier: SimpleEntity | null;
    branch: SimpleEntity | null;
    fiscal_year: SimpleEntity | null;
    purchase_order: { id: number; po_number: string | null } | null;
    goods_receipt: { id: number; gr_number: string | null } | null;
    supplier_invoice_number: string | null;
    supplier_invoice_date: string | null;
    bill_date: string;
    due_date: string;
    payment_terms: string | null;
    currency: string;
    subtotal: string;
    tax_amount: string;
    discount_amount: string;
    grand_total: string;
    amount_paid: string;
    amount_due: string;
    status: SupplierBillStatus;
    notes?: string | null;
    journal_entry_id: number | null;
    created_by?: SimpleEntity | null;
    confirmed_by?: SimpleEntity | null;
    confirmed_at?: string | null;
    items?: SupplierBillItem[];
}

export interface SupplierBillFormData {
    bill_number?: string;
    supplier_id: string;
    branch_id: string;
    fiscal_year_id: string;
    purchase_order_id?: string;
    goods_receipt_id?: string;
    supplier_invoice_number?: string;
    supplier_invoice_date?: Date | null;
    bill_date: Date;
    due_date: Date;
    payment_terms?: string;
    currency: string;
    status: SupplierBillStatus;
    notes?: string;
    items: Array<{
        product_id?: string;
        product_label?: string;
        account_id: string;
        account_label?: string;
        description: string;
        quantity: number;
        unit_price: number;
        discount_percent?: number;
        tax_percent?: number;
        goods_receipt_item_id?: string;
        notes?: string;
    }>;
}

export interface SupplierBillFilters {
    search: string;
    supplier_id: string;
    branch_id: string;
    status: string;
    currency: string;
    bill_date_from: string;
    bill_date_to: string;
    due_date_from: string;
    due_date_to: string;
}
