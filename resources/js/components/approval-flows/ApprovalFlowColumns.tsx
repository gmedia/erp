import { Badge } from '@/components/ui/badge';
import { type ColumnDef } from '@tanstack/react-table';
import { format } from 'date-fns';
import { createSortingHeader, createSelectColumn, createActionsColumn } from '@/utils/columns';

export interface ApprovalFlowStep {
    id?: number;
    approval_flow_id?: number;
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
    user?: { id: number; name: string };
    department?: { id: number; name: string };
}

export interface ApprovalFlow {
    id: number;
    code: string;
    name: string;
    approvable_type: string;
    description: string | null;
    is_active: boolean;
    conditions: any | null;
    created_at: string;
    updated_at: string;
    creator?: { id: number; name: string };
    steps?: ApprovalFlowStep[];
}

export const approvalFlowColumns: ColumnDef<ApprovalFlow>[] = [
    createSelectColumn<ApprovalFlow>(),
    {
        accessorKey: 'code',
        ...createSortingHeader('Code'),
    },
    {
        accessorKey: 'name',
        ...createSortingHeader('Name'),
    },
    {
        accessorKey: 'approvable_type',
        ...createSortingHeader('Approvable Type'),
        cell: ({ row }) => {
            const type = row.getValue('approvable_type') as string;
            if (!type) return '';
            const parts = type.split('\\');
            return parts[parts.length - 1];
        },
    },
    {
        accessorKey: 'is_active',
        ...createSortingHeader('Status'),
        cell: ({ row }) => {
            const isActive = row.getValue('is_active') as boolean;
            return (
                <Badge variant={isActive ? 'default' : 'secondary'}>
                    {isActive ? 'Active' : 'Inactive'}
                </Badge>
            );
        },
    },
    {
        accessorKey: 'created_at',
        ...createSortingHeader('Created At'),
        cell: ({ row }) => {
            const dateStr = row.getValue('created_at') as string;
            return dateStr ? format(new Date(dateStr), 'dd MMM yyyy HH:mm') : '';
        },
    },
    createActionsColumn<ApprovalFlow>(),
];
