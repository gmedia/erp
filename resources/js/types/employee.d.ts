export interface Employee {
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

export interface EmployeeFormData {
    name: string;
    email: string;
    phone: string;
    department: string;
    position: string;
    salary: string;
    hire_date: Date;
}
