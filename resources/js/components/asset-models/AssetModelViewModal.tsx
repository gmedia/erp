'use client';

import { memo } from 'react';

import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/contexts/i18n-context';
import { type AssetModel } from '@/types/entity';

interface AssetModelViewModalProps {
    open: boolean;
    onClose: () => void;
    item: AssetModel | null;
}

export const AssetModelViewModal = memo<AssetModelViewModalProps>(
    function AssetModelViewModal({ open, onClose, item }) {
        const { t } = useTranslation();
        if (!item) return null;

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="View Asset Model"
                description={t('common.view_details')}
            >
                <div className="space-y-4 py-4">
                    <ViewField label="Model Name" value={item.model_name} />
                    <ViewField
                        label="Manufacturer"
                        value={item.manufacturer || '-'}
                    />
                    <ViewField
                        label="Category"
                        value={
                            <Badge variant="outline">
                                {item.category?.name || '-'}
                            </Badge>
                        }
                    />
                    {item.specs && (
                        <ViewField
                            label="Specifications"
                            value={
                                <pre className="overflow-x-auto rounded bg-muted p-2 text-xs break-words whitespace-pre-wrap">
                                    {JSON.stringify(item.specs, null, 2)}
                                </pre>
                            }
                        />
                    )}
                </div>
            </ViewModalShell>
        );
    },
);
