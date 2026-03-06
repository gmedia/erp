import { BaseEntity, SimpleEntity } from './entity';

export type GoodsReceiptStatus = 'draft' | 'confirmed' | 'cancelled';

export interface GoodsReceiptItem {
    id: number;
    purchase_order_item_id: number;
    product: SimpleEntity | null;
    unit: SimpleEntity | null;
    quantity_received: string;
    quantity_accepted: string;
    quantity_rejected: string;
    unit_price: string;
    notes?: string | null;
}

export interface GoodsReceipt extends BaseEntity {
    gr_number: string | null;
    purchase_order: {
        id: number;
        po_number: string | null;
        supplier: SimpleEntity | null;
    } | null;
    warehouse: SimpleEntity | null;
    receipt_date: string;
    supplier_delivery_note?: string | null;
    status: GoodsReceiptStatus;
    notes?: string | null;
    received_by?: SimpleEntity | null;
    confirmed_by?: SimpleEntity | null;
    confirmed_at?: string | null;
    items?: GoodsReceiptItem[];
}

export interface GoodsReceiptFormData {
    gr_number?: string;
    purchase_order_id: string;
    warehouse_id: string;
    receipt_date: Date;
    supplier_delivery_note?: string;
    status: GoodsReceiptStatus;
    received_by?: string;
    notes?: string;
    items: Array<{
        purchase_order_item_id: string;
        product_id: string;
        unit_id: string;
        quantity_received: number;
        quantity_accepted: number;
        quantity_rejected?: number;
        unit_price: number;
        notes?: string;
    }>;
}

export interface GoodsReceiptFilters {
    search: string;
    purchase_order_id: string;
    warehouse_id: string;
    status: string;
    received_by: string;
    receipt_date_from: string;
    receipt_date_to: string;
}
