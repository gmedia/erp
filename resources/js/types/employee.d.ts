export interface Employee extends Record<string, unknown> {
    id: number;
    name: string;
    email: string;
    phone: string;
    department: string;
    position: string;
    salary: string;
    hire_date: string;
    created_at: string;
    updated_at: string;
}

export interface EmployeeFormData extends Record<string, unknown> {
    name: string;
    email: string;
    phone: string;
    department: string;
    position: string;
    salary: string;
    hire_date: Date;
}
