import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { type ApprovalFlow } from './ApprovalFlowColumns';
import { Badge } from '@/components/ui/badge';
import { ScrollArea } from '@/components/ui/scroll-area';

interface ApprovalFlowViewModalProps {
    open: boolean;
    onClose: () => void;
    item: ApprovalFlow | null;
}

export function ApprovalFlowViewModal({ open, onClose, item }: ApprovalFlowViewModalProps) {
    if (!item) return null;

    return (
        <Dialog open={open} onOpenChange={onClose}>
            <DialogContent className="max-w-3xl max-h-[90vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle>{item.name}</DialogTitle>
                </DialogHeader>
                <ScrollArea className="flex-1 overflow-y-auto pr-4">
                    <div className="space-y-6">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <span className="font-semibold block text-gray-500 text-sm">Code</span>
                                <span>{item.code}</span>
                            </div>
                            <div>
                                <span className="font-semibold block text-gray-500 text-sm">Approvable Type</span>
                                <span>{item.approvable_type}</span>
                            </div>
                            <div>
                                <span className="font-semibold block text-gray-500 text-sm">Status</span>
                                <Badge variant={item.is_active ? 'default' : 'secondary'} className="mt-1">
                                    {item.is_active ? 'Active' : 'Inactive'}
                                </Badge>
                            </div>
                            <div>
                                <span className="font-semibold block text-gray-500 text-sm">Created By</span>
                                <span>{item.creator?.name || 'System'}</span>
                            </div>
                        </div>

                        {item.description && (
                            <div>
                                <span className="font-semibold block text-gray-500 text-sm">Description</span>
                                <p className="whitespace-pre-wrap mt-1 text-gray-800">{item.description}</p>
                            </div>
                        )}

                        {item.conditions && (
                            <div>
                                <span className="font-semibold block text-gray-500 text-sm">Conditions</span>
                                <pre className="bg-gray-100 p-3 rounded-md mt-1 text-sm overflow-x-auto whitespace-pre-wrap">
                                    {typeof item.conditions === 'string' ? item.conditions : JSON.stringify(item.conditions, null, 2)}
                                </pre>
                            </div>
                        )}

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
            </DialogContent>
        </Dialog>
    );
}
