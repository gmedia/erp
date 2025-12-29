import { BaseEntity } from './entity';

export interface Position extends BaseEntity {
    name: string;
}

export interface PositionFormData {
    name: string;
}
