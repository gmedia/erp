'use client';

import { memo } from 'react';

import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { useTranslation } from '@/contexts/i18n-context';
import { formatDate } from '@/lib/utils';
import { AssetStocktake } from '@/types/asset-stocktake';

interface AssetStocktakeViewModalProps {
    open: boolean;
    onClose: () => void;
    item: AssetStocktake | null;
}

export const AssetStocktakeViewModal = memo<AssetStocktakeViewModalProps>(
    function AssetStocktakeViewModal({ open, onClose, item }) {
        const { t } = useTranslation();
        if (!item) return null;

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="View Asset Stocktake"
                description={t('common.view_details')}
                contentClassName="sm:max-w-[600px]"
            >
                <div className="space-y-4 py-4">
                    <ViewField label="Reference" value={item.reference} />
                    <ViewField
                        label="Branch"
                        value={item.branch?.name || '-'}
                    />
                    <ViewField
                        label="Planned Date"
                        value={formatDate(item.planned_at)}
                    />
                    <ViewField
                        label="Performed Date"
                        value={
                            item.performed_at
                                ? formatDate(item.performed_at)
                                : '-'
                        }
                    />
                    <ViewField
                        label="Status"
                        value={
                            <span className="capitalize">{item.status}</span>
                        }
                    />
                    <ViewField
                        label="Created By"
                        value={item.created_by?.name || '-'}
                    />
                </div>
            </ViewModalShell>
        );
    },
);
