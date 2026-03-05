import { BaseEntity } from './entity';

export type InventoryStocktakeStatus =
    | 'draft'
    | 'in_progress'
    | 'completed'
    | 'cancelled';

export type InventoryStocktakeItemResult =
    | 'match'
    | 'surplus'
    | 'deficit'
    | 'uncounted';

export interface InventoryStocktakeItem {
    id: number;
    product: { id: number; name: string } | null;
    unit: { id: number; name: string } | null;
    system_quantity: string;
    counted_quantity?: string | null;
    variance?: string | null;
    result: InventoryStocktakeItemResult;
    notes?: string | null;
}

export interface InventoryStocktake extends BaseEntity {
    stocktake_number: string | null;
    warehouse: { id: number; name: string } | null;
    stocktake_date: string;
    status: InventoryStocktakeStatus;
    product_category?: { id: number; name: string } | null;
    notes?: string | null;
    items?: InventoryStocktakeItem[];
}

export interface InventoryStocktakeFilters {
    search?: string;
    warehouse_id?: string;
    product_category_id?: string;
    status?: string;
}

