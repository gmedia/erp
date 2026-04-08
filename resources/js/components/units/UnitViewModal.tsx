'use client';

import { memo } from 'react';

import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { useTranslation } from '@/contexts/i18n-context';
import { formatDate } from '@/lib/utils';
import { type Unit } from './UnitColumns';

interface UnitViewModalProps {
    open: boolean;
    onClose: () => void;
    item: Unit | null;
}

/**
 * UnitViewModal - A read-only modal to display unit details including symbol.
 */
export const UnitViewModal = memo<UnitViewModalProps>(function UnitViewModal({
    open,
    onClose,
    item,
}) {
    const { t } = useTranslation();
    if (!item) return null;

    return (
        <ViewModalShell
            open={open}
            onClose={onClose}
            title="View Unit"
            description={t('common.view_details')}
            contentClassName="sm:max-w-[500px]"
        >
            <div className="space-y-4 py-4">
                <ViewField label="Name" value={item.name} />
                <ViewField label="Symbol" value={item.symbol || '-'} />
                <ViewField
                    label="Created At"
                    value={formatDate(item.created_at)}
                />
                <ViewField
                    label="Updated At"
                    value={formatDate(item.updated_at)}
                />
            </div>
        </ViewModalShell>
    );
});
