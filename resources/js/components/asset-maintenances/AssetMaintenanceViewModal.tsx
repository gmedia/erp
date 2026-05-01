'use client';

import { memo } from 'react';

import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { useTranslation } from '@/contexts/i18n-context';
import { type AssetMaintenance } from '@/types/asset-maintenance';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';

interface AssetMaintenanceViewModalProps {
    open: boolean;
    onClose: () => void;
    item: AssetMaintenance | null;
}

export const AssetMaintenanceViewModal = memo<AssetMaintenanceViewModalProps>(
    function AssetMaintenanceViewModal({ open, onClose, item }) {
        const { t } = useTranslation();
        if (!item) return null;

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="View Asset Maintenance"
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
                                {item.maintenance_type}
                            </span>
                        }
                    />
                    <ViewField
                        label="Status"
                        value={
                            <span className="capitalize">{item.status}</span>
                        }
                    />
                    <ViewField
                        label="Supplier"
                        value={item.supplier || '-'}
                    />
                    <ViewField
                        label="Scheduled At"
                        value={formatDateByRegionalSettings(item.scheduled_at)}
                    />
                    <ViewField
                        label="Performed At"
                        value={formatDateByRegionalSettings(item.performed_at)}
                    />
                    <ViewField
                        label="Cost"
                        value={formatCurrencyByRegionalSettings(
                            Number(item.cost || 0),
                            {
                                locale: 'id-ID',
                                currency: 'IDR',
                                minimumFractionDigits: 0,
                            },
                        )}
                    />
                    <ViewField
                        label="Recorded By"
                        value={item.created_by || '-'}
                    />
                    {item.notes && (
                        <ViewField
                            label="Notes"
                            value={item.notes}
                            className="whitespace-pre-wrap"
                        />
                    )}
                </div>
            </ViewModalShell>
        );
    },
);
