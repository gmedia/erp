export interface Permission {
    id: number;
    name: string;
    display_name: string;
    parent_id: number | null;
    children?: Permission[];
}

export interface PermissionGroup {
    id: number;
    name: string;
    permissions: Permission[];
}
