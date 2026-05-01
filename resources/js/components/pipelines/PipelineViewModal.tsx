'use client';

import { memo } from 'react';

import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/contexts/i18n-context';
import { type Pipeline } from '@/types/entity';
import { formatDateByRegionalSettings } from '@/utils/date-format';

interface PipelineViewModalProps {
    open: boolean;
    onClose: () => void;
    item: Pipeline | null;
}

export const PipelineViewModal = memo<PipelineViewModalProps>(
    function PipelineViewModal({ open, onClose, item }) {
        const { t } = useTranslation();
        if (!item) return null;

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="View Pipeline"
                description={t('common.view_details')}
                contentClassName="max-w-2xl"
            >
                <div className="space-y-4 py-4">
                    <ViewField label="Name" value={item.name} />
                    <ViewField label="Code" value={item.code} />
                    <ViewField label="Entity Type" value={item.entity_type} />
                    <ViewField label="Version" value={item.version} />
                    <ViewField
                        label="Status"
                        value={
                            <Badge
                                variant={
                                    item.is_active ? 'default' : 'destructive'
                                }
                            >
                                {item.is_active ? 'Active' : 'Inactive'}
                            </Badge>
                        }
                    />
                    <ViewField
                        label="Created By"
                        value={item.created_by?.name || 'System'}
                    />
                    <ViewField
                        label="Created At"
                        value={formatDateByRegionalSettings(item.created_at)}
                    />
                    {item.description && (
                        <ViewField
                            label="Description"
                            value={item.description}
                            className="whitespace-pre-wrap"
                        />
                    )}
                    {item.conditions && (
                        <ViewField
                            label="Conditions (JSON)"
                            value={
                                <pre className="overflow-x-auto rounded-md bg-muted p-3 text-sm whitespace-pre-wrap">
                                    {item.conditions}
                                </pre>
                            }
                        />
                    )}
                </div>
            </ViewModalShell>
        );
    },
);
