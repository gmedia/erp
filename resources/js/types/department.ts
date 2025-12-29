import { BaseEntity } from './entity';

export interface Department extends BaseEntity {
    name: string;
}

export interface DepartmentFormData {
    name: string;
}
