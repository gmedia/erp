import { BaseEntity } from './entity';

export interface EmploymentRelation {
    id: number;
    name: string;
}

export interface Employment {
    id: number;
    company_id: number;
    department_id: number;
    position_id: number;
    branch_id: number;
    salary: string | null;
    hire_date: string;
    employment_status: 'regular' | 'intern';
    termination_date: string | null;
    company?: EmploymentRelation | null;
    department?: EmploymentRelation | null;
    position?: EmploymentRelation | null;
    branch?: EmploymentRelation | null;
}

export interface Employee extends BaseEntity {
    employee_id: string;
    name: string;
    email: string;
    phone: string;
    current_employment?: Employment | null;
}

export interface EmployeeFormData {
    employee_id: string;
    name: string;
    email: string;
    phone?: string;
    department_id: string;
    position_id: string;
    branch_id: string;
    company_id: string;
    salary?: string;
    hire_date: Date | string;
    employment_status: 'regular' | 'intern';
    termination_date?: Date | string | null;
}
