import { AssetCategory } from './asset-category';
import { AssetLocation } from './asset-location';
import { AssetMaintenance } from './asset-maintenance';
import { AssetModel } from './asset-model';
import { EntityWithId } from './entity';

export interface AssetMovement extends EntityWithId {
    asset_id: number | string;
    movement_type: string;
    movement_date: string;
    moved_at?: string;
    origin?: string | null;
    destination?: string | null;
    from_branch_id?: number | null;
    from_branch?: { id: number; name: string } | null;
    from_location_id?: number | null;
    from_location?: { id: number; name: string } | null;
    from_employee_id?: number | null;
    from_employee?: { id: number; name: string } | null;
    from_department_id?: number | null;
    from_department?: { id: number; name: string } | null;
    to_branch_id?: number | null;
    to_location_id?: number | null;
    to_branch?: { id: number; name: string } | null;
    to_location?: { id: number; name: string } | null;
    to_department_id?: number | null;
    to_department?: { id: number; name: string } | null;
    to_employee_id?: number | null;
    to_employee?: { id: number; name: string } | null;
    reference: string | null;
    notes: string | null;
    pic: string | null;
    created_by?: string | number | null;
}

export interface AssetStocktakeItem extends EntityWithId {
    stocktake_reference: string;
    stocktake_date: string;
    branch: string;
    expected_location?: string | null;
    found_location?: string | null;
    result?: string | null;
    expected_condition: string;
    found_condition: string;
    status: string;
    notes: string | null;
}

export interface AssetDepreciationLine extends EntityWithId {
    period: string;
    fiscal_year: string;
    amount: string;
    accumulated_depreciation: string;
    accumulated_after?: string;
    book_value: string;
    book_value_after?: string;
    status: string;
}

export interface Asset extends EntityWithId {
    ulid: string;
    asset_code: string;
    name: string;
    asset_model_id: number | null;
    asset_category_id: number;
    serial_number: string | null;
    barcode: string | null;
    branch_id: number;
    asset_location_id: number | null;
    department_id: number | null;
    employee_id: number | null;
    supplier_id: number | null;
    purchase_date: string;
    purchase_cost: string;
    currency: string;
    warranty_end_date: string | null;
    status: 'draft' | 'active' | 'maintenance' | 'disposed' | 'lost';
    condition: 'good' | 'needs_repair' | 'damaged' | null;
    notes: string | null;
    depreciation_method: 'straight_line' | 'declining_balance';
    depreciation_start_date: string | null;
    useful_life_months: number | null;
    salvage_value: string;
    accumulated_depreciation: string;
    book_value: string;
    depreciation_expense_account_id: number | null;
    accumulated_depr_account_id: number | null;
    qrcode_url?: string;

    // Relations
    category?: AssetCategory;
    model?: AssetModel;
    branch?: { id: number; name: string };
    location?: AssetLocation;
    department?: { id: number; name: string };
    employee?: { id: number; name: string };
    supplier?: { id: number; name: string };
    movements?: AssetMovement[];
    maintenances?: AssetMaintenance[];
    stocktake_items?: AssetStocktakeItem[];
    depreciation_lines?: AssetDepreciationLine[];
}
