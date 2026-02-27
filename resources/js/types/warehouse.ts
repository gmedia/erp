import { BaseEntity } from './entity';

export interface Warehouse extends BaseEntity {
    code: string;
    name: string;
    branch_id: number;
    branch?: {
        id: number;
        name: string;
    };
}

export interface WarehouseFormData {
    code: string;
    name: string;
    branch_id: string;
}

export interface WarehouseFilters {
    search: string;
    branch_id: string;
}
