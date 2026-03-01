export interface ApprovalDelegation {
    id: number;
    delegator_user_id: number;
    delegate_user_id: number;
    delegator?: { id: number; name: string };
    delegate?: { id: number; name: string };
    approvable_type: string | null;
    start_date: string;
    end_date: string;
    reason: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface ApprovalDelegationFormData {
    delegator_user_id: string;
    delegate_user_id: string;
    approvable_type?: string;
    start_date: Date;
    end_date: Date;
    reason?: string;
    is_active: string | boolean;
}

export interface ApprovalDelegationFilters {
    search?: string;
    delegator_user_id?: string;
    delegate_user_id?: string;
    is_active?: string;
    start_date_from?: Date | null;
    start_date_to?: Date | null;
}
