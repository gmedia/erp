import { EntityWithId } from './entity';
import { AssetCategory } from './asset-category';
import { AssetModel } from './asset-model';
import { AssetLocation } from './asset-location';

export interface Asset extends EntityWithId {
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
    
    // Relations
    category?: AssetCategory;
    model?: AssetModel;
    branch?: { id: number; name: string };
    location?: AssetLocation;
    department?: { id: number; name: string };
    employee?: { id: number; name: string };
    supplier?: { id: number; name: string };
}
