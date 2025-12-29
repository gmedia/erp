import { BaseEntity } from './entity';

export interface Employee extends BaseEntity {
    name: string;
    email: string;
    phone: string;
    department: string;
    position: string;
    salary: string;
    hire_date: string;
}

export interface EmployeeFormData {
    name: string;
    email: string;
    phone: string;
    department: string;
    position: string;
    salary: string;
    hire_date: Date;
}
