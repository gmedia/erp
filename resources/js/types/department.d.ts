export interface Department extends Record<string, unknown> {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

export interface DepartmentFormData {
    name: string;
}
