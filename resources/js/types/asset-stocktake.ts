import { BaseEntity } from './entity';

export interface AssetStocktake extends BaseEntity {
    reference: string;
    branch_id: number;
    branch: {
        id: number;
        name: string;
    };
    planned_at: string;
    performed_at?: string | null;
    status: 'draft' | 'in_progress' | 'completed' | 'cancelled';
    created_by: {
        id: number;
        name: string;
    } | null;
}

export interface AssetStocktakeFormData {
    reference: string;
    branch_id: string;
    planned_at: Date;
    performed_at?: Date | null;
    status: 'draft' | 'in_progress' | 'completed' | 'cancelled';
}

export interface AssetStocktakeFilters {
    search?: string;
    branch_id?: string;
    status?: string;
    planned_at_from?: string;
    planned_at_to?: string;
}
