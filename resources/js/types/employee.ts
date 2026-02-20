import { BaseEntity } from './entity';

export interface Employee extends BaseEntity {
    employee_id: string;
    name: string;
    email: string;
    phone: string;
    department: { id: number; name: string } | string;
    position: { id: number; name: string } | string;
    branch: { id: number; name: string } | string;
    salary: string | null;
    hire_date: string;
    employment_status: 'regular' | 'intern';
    termination_date: string | null;
}

export interface EmployeeFormData {
    employee_id: string;
    name: string;
    email: string;
    phone?: string;
    department_id: string;
    position_id: string;
    branch_id: string;
    salary?: string;
    hire_date: Date;
    employment_status: 'regular' | 'intern';
    termination_date?: Date | null;
}
