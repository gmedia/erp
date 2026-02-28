import { memo } from 'react';
import { ViewField } from '@/components/common/ViewField';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslation } from '@/contexts/i18n-context';
import { type ApprovalFlow } from '@/types/entity';
import { ScrollArea } from '@/components/ui/scroll-area';

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
            <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
                <DialogContent className="max-w-3xl max-h-[90vh] flex flex-col">
                    <DialogHeader>
                        <DialogTitle>View Approval Flow</DialogTitle>
                        <DialogDescription>
                            {t('common.view_details')}
                        </DialogDescription>
                    </DialogHeader>

                    <ScrollArea className="flex-1 overflow-y-auto pr-4">
                        <div className="space-y-6 py-4">
                            {/* Basic Info Section */}
                            <div className="grid grid-cols-2 gap-4">
                                <ViewField label="Code" value={item.code} />
                                <ViewField label="Name" value={item.name} />
                                <ViewField label="Approvable Type" value={item.approvable_type} />
                                <ViewField
                                    label="Created By"
                                    value={item.creator?.name || 'System'}
                                />
                                <div className="flex flex-col">
                                    <span className="text-sm font-semibold text-gray-500 mb-1">Status</span>
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
                                <ViewField label="Description" value={item.description} />
                            )}

                            {item.conditions && (
                                <div>
                                    <span className="font-semibold block text-gray-500 text-sm">Conditions</span>
                                    <pre className="bg-gray-100 p-3 rounded-md mt-1 text-sm overflow-x-auto whitespace-pre-wrap">
                                        {typeof item.conditions === 'string'
                                            ? item.conditions
                                            : JSON.stringify(item.conditions, null, 2)}
                                    </pre>
                                </div>
                            )}

                            {/* Steps Section */}
                            <div>
                                <span className="font-semibold block text-gray-500 text-sm mb-2">Steps</span>
                                <div className="space-y-3">
                                    {item.steps?.map((step) => (
                                        <div key={step.id || step.step_order} className="border p-4 rounded-md shadow-sm">
                                            <div className="flex justify-between items-center mb-2">
                                                <span className="font-medium">Step {step.step_order}: {step.name}</span>
                                                <Badge variant="outline">{step.required_action.toUpperCase()}</Badge>
                                            </div>
                                            <div className="grid grid-cols-2 gap-2 text-sm text-gray-600">
                                                <div>Approver Type: <span className="font-medium text-gray-900 capitalize">{step.approver_type.replace('_', ' ')}</span></div>
                                                {step.approver_type === 'user' && <div>User: <span className="font-medium text-gray-900">{step.user?.name || `ID: ${step.approver_user_id}`}</span></div>}
                                                {step.approver_type === 'department_head' && <div>Dept: <span className="font-medium text-gray-900">{step.department?.name || `ID: ${step.approver_department_id}`}</span></div>}
                                                {step.approver_type === 'role' && <div>Role ID: <span className="font-medium text-gray-900">{step.approver_role_id}</span></div>}
                                                
                                                {step.auto_approve_after_hours && <div>Auto Approve: <span className="font-medium text-gray-900">{step.auto_approve_after_hours}h</span></div>}
                                                {step.escalate_after_hours && <div>Escalate After: <span className="font-medium text-gray-900">{step.escalate_after_hours}h</span></div>}
                                                <div>Can Reject: <span className="font-medium text-gray-900">{step.can_reject ? 'Yes' : 'No'}</span></div>
                                            </div>
                                        </div>
                                    ))}
                                    {(!item.steps || item.steps.length === 0) && (
                                        <p className="text-gray-500 italic">No steps defined for this approval flow.</p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </ScrollArea>

                    <DialogFooter>
                        <Button type="button" onClick={onClose}>
                            Close
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        );
    }
);
