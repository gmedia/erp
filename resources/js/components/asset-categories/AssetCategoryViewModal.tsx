'use client';

import { memo } from 'react';

import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { useTranslation } from '@/contexts/i18n-context';
import { formatDate } from '@/lib/utils';
import { AssetCategory } from '@/types/asset-category';

interface AssetCategoryViewModalProps {
    open: boolean;
    onClose: () => void;
    item: AssetCategory | null;
}

export const AssetCategoryViewModal = memo<AssetCategoryViewModalProps>(
    function AssetCategoryViewModal({ open, onClose, item }) {
        const { t } = useTranslation();
        if (!item) return null;

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="View Asset Category"
                description={t('common.view_details')}
            >
                <div className="space-y-4 py-4">
                    <ViewField label="Code" value={item.code} />
                    <ViewField label="Name" value={item.name} />
                    <ViewField
                        label="Default Useful Life"
                        value={`${item.useful_life_months_default} months`}
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
