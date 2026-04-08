import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Badge } from '@/components/ui/badge';
import { ApprovalDelegation } from '@/types/approval-delegation';
import { formatDateByRegionalSettings } from '@/utils/date-format';
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
                return formatDateByRegionalSettings(dateString);
            };

            const formatApprovableType = (type: string | null) => {
                if (!type) return 'All Types';
                const parts = type.split('\\');
                return parts.at(-1) ?? 'All Types';
            };

            return (
                <ViewModalShell
                    open={open}
                    onClose={onClose}
                    title="View Approval Delegation"
                    description="Approval Delegation Details"
                    contentClassName="sm:max-w-[500px]"
                >
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
                            value={formatApprovableType(item.approvable_type)}
                        />
                        <div className="space-y-1">
                            <span className="text-sm font-medium text-muted-foreground">
                                Status
                            </span>
                            <div>
                                <Badge
                                    variant={
                                        item.is_active ? 'default' : 'secondary'
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
                </ViewModalShell>
            );
        },
    );
