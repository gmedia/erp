import { BaseEntity, SimpleEntity } from './entity';

export type SupplierReturnReason =
    | 'defective'
    | 'wrong_item'
    | 'excess_quantity'
    | 'damaged'
    | 'other';

export type SupplierReturnStatus = 'draft' | 'confirmed' | 'cancelled';

export interface SupplierReturnItem {
    id: number;
    goods_receipt_item_id: number;
    product: SimpleEntity | null;
    unit: SimpleEntity | null;
    quantity_returned: string;
    unit_price: string;
    notes?: string | null;
}

export interface SupplierReturn extends BaseEntity {
    return_number: string | null;
    purchase_order: {
        id: number;
        po_number: string | null;
    } | null;
    goods_receipt: {
        id: number;
        gr_number: string | null;
    } | null;
    supplier: SimpleEntity | null;
    warehouse: SimpleEntity | null;
    return_date: string;
    reason: SupplierReturnReason;
    status: SupplierReturnStatus;
    notes?: string | null;
    created_by?: SimpleEntity | null;
    items?: SupplierReturnItem[];
}

export interface SupplierReturnFormData {
    return_number?: string;
    purchase_order_id: string;
    goods_receipt_id?: string;
    supplier_id: string;
    warehouse_id: string;
    return_date: Date;
    reason: SupplierReturnReason;
    status: SupplierReturnStatus;
    notes?: string;
    items: Array<{
        goods_receipt_item_id: string;
        product_id: string;
        product_label?: string;
        unit_id?: string;
        unit_label?: string;
        quantity_returned: number;
        unit_price: number;
        notes?: string;
    }>;
}

export interface SupplierReturnFilters {
    search: string;
    purchase_order_id: string;
    goods_receipt_id: string;
    supplier_id: string;
    warehouse_id: string;
    reason: string;
    status: string;
    return_date_from: string;
    return_date_to: string;
}
