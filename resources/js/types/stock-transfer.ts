import { BaseEntity } from './entity';

export type StockTransferStatus =
    | 'draft'
    | 'pending_approval'
    | 'approved'
    | 'in_transit'
    | 'received'
    | 'cancelled';

export interface StockTransferItem {
    id: number;
    product: { id: number; name: string } | null;
    unit: { id: number; name: string } | null;
    quantity: string;
    quantity_received: string;
    unit_cost: string;
    notes?: string | null;
}

export interface StockTransfer extends BaseEntity {
    transfer_number: string | null;
    from_warehouse: { id: number; name: string } | null;
    to_warehouse: { id: number; name: string } | null;
    transfer_date: string;
    expected_arrival_date?: string | null;
    status: StockTransferStatus;
    notes?: string | null;
    requested_by?: { id: number; name: string } | null;
    items?: StockTransferItem[];
}

export interface StockTransferFilters {
    search?: string;
    from_warehouse_id?: string;
    to_warehouse_id?: string;
    status?: string;
}
