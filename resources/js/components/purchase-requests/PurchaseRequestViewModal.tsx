import { ViewField } from '@/components/common/ViewField';
import {
    ViewModalItemsTable,
    type ViewModalItemsTableColumn,
} from '@/components/common/ViewModalItemsTable';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Badge } from '@/components/ui/badge';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import {
    formatCurrencyByRegionalSettings,
    formatNumberByRegionalSettings,
} from '@/utils/number-format';
import React from 'react';

import {
    PurchaseRequest,
    type PurchaseRequestItem,
} from '@/types/purchase-request';

interface PurchaseRequestViewModalProps {
    open: boolean;
    onClose: () => void;
    item: PurchaseRequest | null;
}

const formatAmount = (value: string | number | null | undefined): string =>
    formatCurrencyByRegionalSettings(value ?? 0, {
        locale: 'id-ID',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

const formatQuantity = (value: string | number | null | undefined): string =>
    formatNumberByRegionalSettings(value ?? 0, {
        locale: 'id-ID',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });

const purchaseRequestItemColumns: ViewModalItemsTableColumn<PurchaseRequestItem>[] =
    [
        {
            key: 'product',
            header: 'Product',
            render: (item) => item.product?.name || '-',
        },
        {
            key: 'unit',
            header: 'Unit',
            render: (item) => item.unit?.name || '-',
        },
        {
            key: 'quantity',
            header: 'Quantity',
            align: 'right',
            render: (item) => formatQuantity(item.quantity),
        },
        {
            key: 'estimated_unit_price',
            header: 'Est. Unit Price',
            align: 'right',
            render: (item) => formatAmount(item.estimated_unit_price),
        },
        {
            key: 'estimated_total',
            header: 'Est. Total',
            align: 'right',
            render: (item) => formatAmount(item.estimated_total),
        },
    ];

export const PurchaseRequestViewModal = React.memo(
    ({ item, open, onClose }: PurchaseRequestViewModalProps) => {
        if (!item) return null;

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="Purchase Request Details"
                description="View details and requested items"
                contentClassName="flex max-h-[90vh] max-w-[95vw] flex-col overflow-hidden sm:max-w-7xl"
            >
                <div className="min-h-0 flex-1 overflow-y-auto sm:pr-4">
                    <div className="space-y-6 py-2">
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <ViewField
                                label="PR Number"
                                value={item.pr_number}
                            />
                            <ViewField
                                label="Branch"
                                value={item.branch?.name}
                            />
                            <ViewField
                                label="Department"
                                value={item.department?.name}
                            />
                            <ViewField
                                label="Requested By"
                                value={item.requester?.name}
                            />
                            <ViewField
                                label="Request Date"
                                value={formatDateByRegionalSettings(
                                    item.request_date,
                                )}
                            />
                            <ViewField
                                label="Required Date"
                                value={formatDateByRegionalSettings(
                                    item.required_date,
                                )}
                            />
                            <ViewField
                                label="Priority"
                                value={<Badge>{item.priority}</Badge>}
                            />
                            <ViewField
                                label="Status"
                                value={
                                    <Badge variant="outline">
                                        {item.status}
                                    </Badge>
                                }
                            />
                            <ViewField
                                label="Estimated Amount"
                                value={formatAmount(item.estimated_amount)}
                            />
                            <ViewField
                                label="Notes"
                                value={item.notes || '-'}
                            />
                        </div>

                        <ViewModalItemsTable
                            items={item.items}
                            columns={purchaseRequestItemColumns}
                            minWidthClassName="min-w-[720px]"
                            getRowKey={(row) => row.id}
                        />
                    </div>
                </div>
            </ViewModalShell>
        );
    },
);
