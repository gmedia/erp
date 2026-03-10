import { BaseEntity, SimpleEntity } from './entity';

export type PurchaseRequestPriority = 'low' | 'normal' | 'high' | 'urgent';
export type PurchaseRequestStatus =
    | 'draft'
    | 'pending_approval'
    | 'approved'
    | 'rejected'
    | 'partially_ordered'
    | 'fully_ordered'
    | 'cancelled';

export interface PurchaseRequestItem {
    id: number;
    product: SimpleEntity | null;
    unit: SimpleEntity | null;
    quantity: string;
    quantity_ordered: string;
    estimated_unit_price: string | null;
    estimated_total: string | null;
    notes?: string | null;
}

export interface PurchaseRequest extends BaseEntity {
    pr_number: string | null;
    branch: SimpleEntity | null;
    department: SimpleEntity | null;
    requester: SimpleEntity | null;
    request_date: string;
    required_date: string | null;
    priority: PurchaseRequestPriority;
    status: PurchaseRequestStatus;
    estimated_amount: string | null;
    notes?: string | null;
    rejection_reason?: string | null;
    approved_by?: SimpleEntity | null;
    approved_at?: string | null;
    items?: PurchaseRequestItem[];
}

export interface PurchaseRequestFormData {
    pr_number?: string;
    branch_id: string;
    department_id?: string;
    requested_by?: string;
    request_date: Date;
    required_date?: Date | null;
    priority: PurchaseRequestPriority;
    status: PurchaseRequestStatus;
    estimated_amount?: number;
    notes?: string;
    rejection_reason?: string;
    items: Array<{
        product_id: string;
        product_label?: string;
        unit_id: string;
        unit_label?: string;
        quantity: number;
        estimated_unit_price?: number;
        notes?: string;
    }>;
}

export interface PurchaseRequestFilters {
    search: string;
    branch_id: string;
    department_id: string;
    requested_by: string;
    priority: string;
    status: string;
    request_date_from: string;
    request_date_to: string;
}
