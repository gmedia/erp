'use client';

import { memo } from 'react';

import { type AssetMovement } from '@/components/asset-movements/AssetMovementColumns';
import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { useTranslation } from '@/contexts/i18n-context';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';

interface AssetMovementViewModalProps {
    open: boolean;
    onClose: () => void;
    item: AssetMovement | null;
}

export const AssetMovementViewModal = memo<AssetMovementViewModalProps>(
    function AssetMovementViewModal({ open, onClose, item }) {
        const { t } = useTranslation();
        if (!item) return null;

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="View Asset Movement"
                description={t('common.view_details')}
                contentClassName="sm:max-w-[600px]"
            >
                <div className="space-y-4 py-4">
                    <ViewField
                        label="Asset"
                        value={`${item.asset?.name || '-'} (${item.asset?.asset_code || '-'})`}
                    />
                    <ViewField
                        label="Type"
                        value={
                            <span className="capitalize">
                                {item.movement_type}
                            </span>
                        }
                    />
                    <ViewField
                        label="Date"
                        value={formatDateTimeByRegionalSettings(item.moved_at)}
                    />
                    <ViewField
                        label="Reference"
                        value={item.reference || '-'}
                    />

                    <hr />

                    <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                        <div className="space-y-2">
                            <span className="block text-xs font-medium text-muted-foreground">
                                Origin
                            </span>
                            <div className="rounded bg-muted/50 p-2">
                                {item.from_branch && (
                                    <div>{item.from_branch}</div>
                                )}
                                {item.from_location && (
                                    <div className="text-xs text-muted-foreground">
                                        {item.from_location}
                                    </div>
                                )}
                                {item.from_employee && (
                                    <div className="text-xs text-primary">
                                        {item.from_employee}
                                    </div>
                                )}
                                {!item.from_branch && !item.from_employee && (
                                    <span className="text-xs text-muted-foreground italic">
                                        Initial/Acquired
                                    </span>
                                )}
                            </div>
                        </div>
                        <div className="space-y-2">
                            <span className="block text-xs font-medium text-muted-foreground">
                                Destination
                            </span>
                            <div className="rounded bg-primary/5 p-2">
                                {item.to_branch && <div>{item.to_branch}</div>}
                                {item.to_location && (
                                    <div className="text-xs text-muted-foreground">
                                        {item.to_location}
                                    </div>
                                )}
                                {item.to_employee && (
                                    <div className="text-xs text-primary">
                                        {item.to_employee}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {item.notes && (
                        <ViewField
                            label="Notes"
                            value={item.notes}
                            className="whitespace-pre-wrap"
                        />
                    )}

                    <ViewField
                        label="Recorded By"
                        value={item.created_by || '-'}
                    />
                </div>
            </ViewModalShell>
        );
    },
);
