import { BaseEntity } from './entity';

export type StockAdjustmentStatus =
    | 'draft'
    | 'pending_approval'
    | 'approved'
    | 'cancelled';

export type StockAdjustmentType =
    | 'damage'
    | 'expired'
    | 'shrinkage'
    | 'correction'
    | 'stocktake_result'
    | 'initial_stock'
    | 'other';

export interface StockAdjustmentItem {
    id: number;
    product: { id: number; name: string } | null;
    unit: { id: number; name: string } | null;
    quantity_before: string;
    quantity_adjusted: string;
    quantity_after: string;
    unit_cost: string;
    total_cost: string;
    reason?: string | null;
}

export interface StockAdjustment extends BaseEntity {
    adjustment_number: string | null;
    warehouse: { id: number; name: string } | null;
    adjustment_date: string;
    adjustment_type: StockAdjustmentType;
    status: StockAdjustmentStatus;
    inventory_stocktake?: { id: number; stocktake_number: string | null } | null;
    notes?: string | null;
    items?: StockAdjustmentItem[];
}

export interface StockAdjustmentFilters {
    search?: string;
    warehouse_id?: string;
    status?: string;
    adjustment_type?: string;
}
