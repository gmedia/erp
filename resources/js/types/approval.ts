export interface ApprovalFlow {
    id: number;
    name: string;
    code: string;
    approvable_type: string;
    description: string | null;
    is_active: boolean;
    conditions: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
}

export interface ApprovalFlowStep {
    id: number;
    approval_flow_id: number;
    step_order: number;
    name: string;
    approver_type: 'user' | 'role' | 'department_head';
    approver_user_id: number | null;
    approver_role_id: number | null;
    approver_department_id: number | null;
    required_action: 'approve' | 'review' | 'acknowledge';
    auto_approve_after_hours: number | null;
    escalate_after_hours: number | null;
    escalation_user_id: number | null;
    can_reject: boolean;
    created_at: string;
    updated_at: string;
}

export interface ApprovalRequest {
    id: number;
    approval_flow_id: number;
    approvable_type: string;
    approvable_id: number;
    current_step_order: number;
    status: 'pending' | 'in_progress' | 'approved' | 'rejected' | 'cancelled';
    submitted_by: number;
    submitted_at: string;
    completed_at: string | null;
    created_at: string;
    updated_at: string;
    approvable?: {
        ulid?: string;
        asset_code?: string;
        name?: string;
        description?: string;
        [key: string]: unknown;
    };
    submitter?: {
        id: number;
        name: string;
        email: string;
    };
    steps?: ApprovalRequestStep[];
}

export interface ApprovalRequestStep {
    id: number;
    approval_request_id: number;
    approval_flow_step_id: number;
    step_order: number;
    status: 'pending' | 'approved' | 'rejected' | 'skipped';
    acted_by: number | null;
    delegated_from: number | null;
    action: 'approve' | 'reject' | 'skip' | 'auto_approve' | null;
    comments: string | null;
    acted_at: string | null;
    due_at: string | null;
    created_at: string;
    updated_at: string;
    actor?: {
        id: number;
        name: string;
        email: string;
    };
    delegator?: {
        id: number;
        name: string;
        email: string;
    };
    acted_by_user?: {
        id: number;
        name: string;
        email: string;
    };
    delegated_from_user?: {
        id: number;
        name: string;
        email: string;
    };
    flow_step?: ApprovalFlowStep;
    flowStep?: ApprovalFlowStep; // Alias for backward compatibility or components
    request: ApprovalRequest;
}

export interface ApprovalAuditLog {
    id: number;
    approval_request_id: number | null;
    approvable_type: string;
    approvable_id: number;
    event:
        | 'submitted'
        | 'step_approved'
        | 'step_rejected'
        | 'step_skipped'
        | 'auto_approved'
        | 'escalated'
        | 'delegated'
        | 'cancelled'
        | 'resubmitted'
        | 'completed';
    actor_user_id: number | null;
    step_order: number | null;
    metadata: Record<string, unknown> | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
}
