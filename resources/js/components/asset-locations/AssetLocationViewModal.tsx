'use client';

import { memo } from 'react';

import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/contexts/i18n-context';
import { type AssetLocation } from '@/types/entity';

interface AssetLocationViewModalProps {
    open: boolean;
    onClose: () => void;
    item: AssetLocation | null;
}

export const AssetLocationViewModal = memo<AssetLocationViewModalProps>(
    function AssetLocationViewModal({ open, onClose, item }) {
        const { t } = useTranslation();
        if (!item) return null;

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="View Asset Location"
                description={t('common.view_details')}
            >
                <div className="space-y-4 py-4">
                    <ViewField label="Code" value={item.code} />
                    <ViewField label="Name" value={item.name} />
                    <ViewField
                        label="Branch"
                        value={
                            <Badge variant="outline">
                                {item.branch?.name || '-'}
                            </Badge>
                        }
                    />
                    <ViewField
                        label="Parent Location"
                        value={item.parent?.name || 'None'}
                    />
                </div>
            </ViewModalShell>
        );
    },
);
