import { BaseEntity } from './entity';

export interface AssetLocation extends BaseEntity {
    code: string;
    name: string;
    branch_id: number;
    parent_id: number | null;
    branch?: {
        id: number;
        name: string;
    };
    parent?: {
        id: number;
        name: string;
    } | null;
}

export interface AssetLocationFormData {
    code: string;
    name: string;
    branch_id: string;
    parent_id?: string;
}

export interface AssetLocationFilters {
    search: string;
    branch_id: string;
    parent_id: string;
}
