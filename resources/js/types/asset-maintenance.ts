import { EntityWithId } from './entity';

export interface AssetMaintenance extends EntityWithId {
    asset_id: number;
    asset?: {
        id: number;
        name: string | null;
        asset_code: string | null;
    };
    maintenance_type: 'preventive' | 'corrective' | 'calibration' | 'other';
    status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled';
    scheduled_at: string | null;
    performed_at: string | null;
    supplier_id: number | null;
    supplier: string | null;
    cost: string;
    notes: string | null;
    created_by_id: number | null;
    created_by: string | null;
}
