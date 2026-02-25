import { BaseEntity } from './entity';

export interface Warehouse extends BaseEntity {
    name: string;
}

export interface WarehouseFormData {
    name: string;
}
