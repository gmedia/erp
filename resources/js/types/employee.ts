import { BaseEntity } from './entity';

export interface Employee extends BaseEntity {
    name: string;
    email: string;
    phone: string;
    department: { id: number; name: string } | string;
    position: { id: number; name: string } | string;
    branch: { id: number; name: string } | string;
    salary: string;
    hire_date: string;
}

export interface EmployeeFormData {
    name: string;
    email: string;
    phone: string;
    department_id: string;
    position_id: string;
    branch_id: string;
    salary: string;
    hire_date: Date;
}
