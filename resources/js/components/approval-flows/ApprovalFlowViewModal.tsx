import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/contexts/i18n-context';
import { type ApprovalFlow } from '@/types/entity';
import { memo } from 'react';

interface ApprovalFlowViewModalProps {
    open: boolean;
    onClose: () => void;
    item: ApprovalFlow | null;
}

export const ApprovalFlowViewModal = memo<ApprovalFlowViewModalProps>(
    function ApprovalFlowViewModal({ open, onClose, item }) {
        const { t } = useTranslation();
        if (!item) return null;

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="View Approval Flow"
                description={t('common.view_details')}
                contentClassName="flex max-h-[90vh] max-w-3xl flex-col overflow-hidden"
            >
                <div className="min-h-0 flex-1 overflow-y-auto sm:pr-4">
                    <div className="space-y-6 py-4">
                        {/* Basic Info Section */}
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <ViewField label="Code" value={item.code} />
                            <ViewField label="Name" value={item.name} />
                            <ViewField
                                label="Approvable Type"
                                value={item.approvable_type}
                            />
                            <ViewField
                                label="Created By"
                                value={item.creator?.name || 'System'}
                            />
                            <div className="flex flex-col">
                                <span className="mb-1 text-sm font-semibold text-muted-foreground">
                                    Status
                                </span>
                                <div>
                                    <Badge
                                        variant={
                                            item.is_active
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {item.is_active ? 'Active' : 'Inactive'}
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        {/* Details Section */}
                        {item.description && (
                            <ViewField
                                label="Description"
                                value={item.description}
                            />
                        )}

                        {item.conditions && (
                            <div>
                                <span className="block text-sm font-semibold text-muted-foreground">
                                    Conditions
                                </span>
                                <pre className="mt-1 overflow-x-auto rounded-md bg-muted p-3 text-sm whitespace-pre-wrap text-foreground">
                                    {typeof item.conditions === 'string'
                                        ? item.conditions
                                        : JSON.stringify(
                                              item.conditions,
                                              null,
                                              2,
                                          )}
                                </pre>
                            </div>
                        )}

                        {/* Steps Section */}
                        <div>
                            <span className="mb-2 block text-sm font-semibold text-muted-foreground">
                                Steps
                            </span>
                            <div className="space-y-3">
                                {item.steps?.map((step) => (
                                    <div
                                        key={step.id || step.step_order}
                                        className="rounded-md border bg-card p-4 shadow-sm"
                                    >
                                        <div className="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                            <span className="font-medium">
                                                Step {step.step_order}:{' '}
                                                {step.name}
                                            </span>
                                            <Badge variant="outline">
                                                {step.required_action.toUpperCase()}
                                            </Badge>
                                        </div>
                                        <div className="grid grid-cols-1 gap-2 text-sm text-muted-foreground sm:grid-cols-2">
                                            <div>
                                                Approver Type:{' '}
                                                <span className="font-medium text-foreground capitalize">
                                                    {step.approver_type.replace(
                                                        '_',
                                                        ' ',
                                                    )}
                                                </span>
                                            </div>
                                            {step.approver_type === 'user' && (
                                                <div>
                                                    User:{' '}
                                                    <span className="font-medium text-foreground">
                                                        {step.user?.name ||
                                                            `ID: ${step.approver_user_id}`}
                                                    </span>
                                                </div>
                                            )}
                                            {step.approver_type ===
                                                'department_head' && (
                                                <div>
                                                    Dept:{' '}
                                                    <span className="font-medium text-foreground">
                                                        {step.department
                                                            ?.name ||
                                                            `ID: ${step.approver_department_id}`}
                                                    </span>
                                                </div>
                                            )}
                                            {step.approver_type === 'role' && (
                                                <div>
                                                    Role ID:{' '}
                                                    <span className="font-medium text-foreground">
                                                        {step.approver_role_id}
                                                    </span>
                                                </div>
                                            )}

                                            {step.auto_approve_after_hours && (
                                                <div>
                                                    Auto Approve:{' '}
                                                    <span className="font-medium text-foreground">
                                                        {
                                                            step.auto_approve_after_hours
                                                        }
                                                        h
                                                    </span>
                                                </div>
                                            )}
                                            {step.escalate_after_hours && (
                                                <div>
                                                    Escalate After:{' '}
                                                    <span className="font-medium text-foreground">
                                                        {
                                                            step.escalate_after_hours
                                                        }
                                                        h
                                                    </span>
                                                </div>
                                            )}
                                            <div>
                                                Can Reject:{' '}
                                                <span className="font-medium text-foreground">
                                                    {step.can_reject
                                                        ? 'Yes'
                                                        : 'No'}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                                {(!item.steps || item.steps.length === 0) && (
                                    <p className="text-muted-foreground italic">
                                        No steps defined for this approval flow.
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </ViewModalShell>
        );
    },
);
