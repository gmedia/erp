import { BaseEntity } from './entity';

export interface AssetCategory extends BaseEntity {
    code: string;
    name: string;
    useful_life_months_default: number;
}

export interface AssetCategoryFormData {
    code: string;
    name: string;
    useful_life_months_default: number;
}
