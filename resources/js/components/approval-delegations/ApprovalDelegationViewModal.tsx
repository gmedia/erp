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
import { ApprovalDelegation } from '@/types/approval-delegation';
import { format } from 'date-fns';
import { memo } from 'react';

interface ApprovalDelegationViewModalProps {
    open: boolean;
    onClose: () => void;
    item: ApprovalDelegation | null;
}

export const ApprovalDelegationViewModal =
    memo<ApprovalDelegationViewModalProps>(
        function ApprovalDelegationViewModal({ open, onClose, item }) {
            if (!item) return null;

            const formatDate = (dateString?: string | null) => {
                if (!dateString) return '-';
                return format(new Date(dateString), 'dd MMM yyyy');
            };

            const formatApprovableType = (type: string | null) => {
                if (!type) return 'All Types';
                const parts = type.split('\\');
                return parts[parts.length - 1];
            };

            return (
                <Dialog
                    open={open}
                    onOpenChange={(isOpen) => !isOpen && onClose()}
                >
                    <DialogContent className="sm:max-w-[500px]">
                        <DialogHeader>
                            <DialogTitle>View Approval Delegation</DialogTitle>
                            <DialogDescription>
                                Approval Delegation Details
                            </DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4 py-4">
                            <ViewField
                                label="Delegator"
                                value={item.delegator?.name || '-'}
                            />
                            <ViewField
                                label="Delegate"
                                value={item.delegate?.name || '-'}
                            />
                            <ViewField
                                label="Start Date"
                                value={formatDate(item.start_date)}
                            />
                            <ViewField
                                label="End Date"
                                value={formatDate(item.end_date)}
                            />
                            <ViewField
                                label="Approvable Type"
                                value={formatApprovableType(
                                    item.approvable_type,
                                )}
                            />
                            <div className="space-y-1">
                                <span className="text-sm font-medium text-muted-foreground">
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
                            {item.reason && (
                                <ViewField label="Reason" value={item.reason} />
                            )}
                        </div>

                        <DialogFooter>
                            <Button type="button" onClick={onClose}>
                                Close
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            );
        },
    );
