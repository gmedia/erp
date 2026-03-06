import { BaseEntity, SimpleEntity } from './entity';

export type PurchaseOrderStatus =
    | 'draft'
    | 'pending_approval'
    | 'confirmed'
    | 'rejected'
    | 'partially_received'
    | 'fully_received'
    | 'cancelled'
    | 'closed';

export interface PurchaseOrderItem {
    id: number;
    purchase_request_item_id: number | null;
    product: SimpleEntity | null;
    unit: SimpleEntity | null;
    quantity: string;
    quantity_received: string;
    unit_price: string;
    discount_percent: string;
    tax_percent: string;
    line_total: string;
    notes?: string | null;
}

export interface PurchaseOrder extends BaseEntity {
    po_number: string | null;
    supplier: SimpleEntity | null;
    warehouse: SimpleEntity | null;
    order_date: string;
    expected_delivery_date: string | null;
    payment_terms: string | null;
    currency: string;
    subtotal: string;
    tax_amount: string;
    discount_amount: string;
    grand_total: string;
    status: PurchaseOrderStatus;
    notes?: string | null;
    shipping_address?: string | null;
    approved_by?: SimpleEntity | null;
    approved_at?: string | null;
    items?: PurchaseOrderItem[];
}

export interface PurchaseOrderFormData {
    po_number?: string;
    supplier_id: string;
    warehouse_id: string;
    order_date: Date;
    expected_delivery_date?: Date | null;
    payment_terms?: string;
    currency: string;
    status: PurchaseOrderStatus;
    notes?: string;
    shipping_address?: string;
    items: Array<{
        purchase_request_item_id?: string;
        product_id: string;
        unit_id: string;
        quantity: number;
        unit_price: number;
        discount_percent?: number;
        tax_percent?: number;
        notes?: string;
    }>;
}

export interface PurchaseOrderFilters {
    search: string;
    supplier_id: string;
    warehouse_id: string;
    status: string;
    currency: string;
    order_date_from: string;
    order_date_to: string;
}
