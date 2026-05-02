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
} from '@/utils/number-format';
import React from 'react';

import { ArReceipt, type ArReceiptAllocation } from '@/types/ar-receipt';

interface ArReceiptViewModalProps {
    open: boolean;
    onClose: () => void;
    item: ArReceipt | null;
}

type FormatValueInput = string | number | null | undefined;

function createArReceiptAllocationColumns(
    formatAmount: (value: FormatValueInput) => string,
): ViewModalItemsTableColumn<ArReceiptAllocation>[] {
    return [
        {
            key: 'invoice',
            header: 'Invoice',
            render: (item) => item.invoice_number || '-',
        },
        {
            key: 'allocated_amount',
            header: 'Allocated Amount',
            align: 'right',
            render: (item) => formatAmount(item.allocated_amount),
        },
        {
            key: 'discount_given',
            header: 'Discount Given',
            align: 'right',
            render: (item) => formatAmount(item.discount_given),
        },
    ];
}

export const ArReceiptViewModal = React.memo(
    ({ item, open, onClose }: ArReceiptViewModalProps) => {
        if (!item) return null;

        const formatAmount = (value: FormatValueInput) =>
            formatCurrencyByRegionalSettings(value ?? 0, {
                locale: 'id-ID',
                currency: item.currency || undefined,
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

        const allocationColumns = createArReceiptAllocationColumns(formatAmount);

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="AR Receipt Details"
                description={`Receipt ${item.receipt_number || 'N/A'}`}
            >
                <div className="space-y-4 py-4">
                    <div className="grid grid-cols-2 gap-4">
                        <ViewField label="Receipt Number" value={item.receipt_number || '-'} />
                        <ViewField label="Customer" value={item.customer?.name || '-'} />
                        <ViewField label="Branch" value={item.branch?.name || '-'} />
                        <ViewField label="Fiscal Year" value={item.fiscal_year?.name || '-'} />
                        <ViewField
                            label="Receipt Date"
                            value={formatDateByRegionalSettings(item.receipt_date, {
                                locale: 'id-ID',
                            })}
                        />
                        <ViewField
                            label="Payment Method"
                            value={<Badge variant="outline">{item.payment_method.replace('_', ' ')}</Badge>}
                        />
                        <ViewField label="Bank Account" value={item.bank_account?.name || '-'} />
                        <ViewField label="Currency" value={item.currency || '-'} />
                        <ViewField
                            label="Status"
                            value={<Badge variant="outline">{item.status.replace('_', ' ')}</Badge>}
                        />
                        <ViewField
                            label="Total Amount"
                            value={formatAmount(item.total_amount)}
                        />
                        <ViewField
                            label="Total Allocated"
                            value={formatAmount(item.total_allocated)}
                        />
                        <ViewField
                            label="Total Unallocated"
                            value={formatAmount(item.total_unallocated)}
                        />
                        <ViewField label="Reference" value={item.reference || '-'} />
                    </div>

                    {item.notes && (
                        <ViewField label="Notes" value={item.notes} />
                    )}

                    {item.allocations && item.allocations.length > 0 && (
                        <div className="pt-4">
                            <h3 className="mb-2 text-lg font-semibold">Allocations</h3>
                            <ViewModalItemsTable
                                items={item.allocations}
                                columns={allocationColumns}
                                minWidthClassName="min-w-[860px]"
                                getRowKey={(row) => row.id}
                            />
                        </div>
                    )}

                    <div className="pt-4 text-sm text-muted-foreground">
                        <div>Created by: {item.created_by?.name || 'System'}</div>
                        {item.confirmed_at && (
                            <div>
                                Confirmed by: {item.confirmed_by?.name || 'System'} on{' '}
                                {formatDateByRegionalSettings(item.confirmed_at, {
                                    locale: 'id-ID',
                                })}
                            </div>
                        )}
                    </div>
                </div>
            </ViewModalShell>
        );
    },
);