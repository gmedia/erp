import { BaseEntity } from './entity';

export interface AssetModel extends BaseEntity {
    model_name: string;
    manufacturer: string | null;
    asset_category_id: number;
    specs: Record<string, unknown> | null;
    category?: {
        id: number;
        name: string;
    };
}

export interface AssetModelFormData {
    model_name: string;
    manufacturer: string;
    asset_category_id: string;
    specs: string;
}

export interface AssetModelFilters {
    search: string;
    asset_category_id: string;
}
