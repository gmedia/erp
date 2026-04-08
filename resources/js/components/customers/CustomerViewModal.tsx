'use client';

import { memo } from 'react';

import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/contexts/i18n-context';

import { Customer } from '@/types/entity';

interface CustomerViewModalProps {
    open: boolean;
    onClose: () => void;
    item: Customer | null;
}

/**
 * CustomerViewModal - A read-only modal to display customer details.
 * Similar layout to Edit modal but with static text instead of form inputs.
 */
export const CustomerViewModal = memo<CustomerViewModalProps>(
    function CustomerViewModal({ open, onClose, item }) {
        const { t } = useTranslation();
        if (!item) return null;

        const branchName =
            typeof item.branch === 'object' ? item.branch.name : item.branch;

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="View Customer"
                description={t('common.view_details')}
            >
                <div className="space-y-4 py-4">
                    <ViewField label="Name" value={item.name} />
                    <ViewField label="Email" value={item.email} />
                    <ViewField label="Phone" value={item.phone} />
                    <ViewField label="Address" value={item.address} />
                    <ViewField label="Branch" value={branchName} />
                    <ViewField
                        label="Category"
                        value={
                            typeof item.category === 'object'
                                ? item.category.name
                                : item.category
                        }
                    />
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <span className="text-sm font-medium text-muted-foreground">
                            Status
                        </span>
                        <Badge
                            variant={
                                item.status === 'active'
                                    ? 'default'
                                    : 'destructive'
                            }
                        >
                            {item.status === 'active' ? 'Active' : 'Inactive'}
                        </Badge>
                    </div>
                    {item.notes && <ViewField label="Notes" value={item.notes} />}
                </div>
            </ViewModalShell>
        );
    },
);
