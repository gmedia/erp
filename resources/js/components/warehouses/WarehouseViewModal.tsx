'use client';

import { memo } from 'react';

import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/contexts/i18n-context';
import { type Warehouse } from '@/types/entity';

interface WarehouseViewModalProps {
    open: boolean;
    onClose: () => void;
    item: Warehouse | null;
}

export const WarehouseViewModal = memo<WarehouseViewModalProps>(
    function WarehouseViewModal({ open, onClose, item }) {
        const { t } = useTranslation();
        if (!item) return null;

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="View Warehouse"
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
                </div>
            </ViewModalShell>
        );
    },
);
