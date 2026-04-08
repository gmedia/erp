'use client';

import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { useTranslation } from '@/contexts/i18n-context';
import { formatDate } from '@/lib/utils';
import { type CoaVersion } from '@/types/coa-version';
import { memo } from 'react';

interface CoaVersionViewModalProps {
    open: boolean;
    onClose: () => void;
    item: CoaVersion | null;
}

export const CoaVersionViewModal = memo<CoaVersionViewModalProps>(
    function CoaVersionViewModal({ open, onClose, item }) {
        const { t } = useTranslation();
        if (!item) return null;

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="View COA Version"
                description={t('common.view_details')}
            >
                <div className="space-y-4 py-4">
                    <ViewField label="Name" value={item.name} />
                    <ViewField
                        label="Fiscal Year"
                        value={item.fiscal_year?.name || '-'}
                    />
                    <ViewField
                        label="Status"
                        value={item.status.toUpperCase()}
                    />
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
    },
);
