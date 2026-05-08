import { ViewField } from '@/components/common/ViewField';
import {
    ViewModalItemsTable,
    type ViewModalItemsTableColumn,
} from '@/components/common/ViewModalItemsTable';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Badge } from '@/components/ui/badge';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import React from 'react';

import { ApPayment, type ApPaymentAllocation } from '@/types/ap-payment';

interface ApPaymentViewModalProps {
    open: boolean;
    onClose: () => void;
    item: ApPayment | null;
}

type FormatValueInput = string | number | null | undefined;

function createApPaymentAllocationColumns(
    formatAmount: (value: FormatValueInput) => string,
): ViewModalItemsTableColumn<ApPaymentAllocation>[] {
    return [
        {
            key: 'bill_number',
            header: 'Bill Number',
            render: (item) => item.bill_number || '-',
        },
        {
            key: 'allocated_amount',
            header: 'Allocated Amount',
            align: 'right',
            render: (item) => formatAmount(item.allocated_amount),
        },
        {
            key: 'discount_taken',
            header: 'Discount Taken',
            align: 'right',
            render: (item) => formatAmount(item.discount_taken),
        },
        {
            key: 'notes',
            header: 'Notes',
            render: (item) => item.notes || '-',
        },
    ];
}

export const ApPaymentViewModal = React.memo(
    ({ item, open, onClose }: ApPaymentViewModalProps) => {
        if (!item) return null;

        const formatAmount = (value: FormatValueInput) =>
            formatCurrencyByRegionalSettings(value ?? 0, {
                locale: 'id-ID',
                currency: item.currency || undefined,
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

        const allocationColumns =
            createApPaymentAllocationColumns(formatAmount);

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="AP Payment Details"
                description="View payment summary and allocations"
                contentClassName="flex max-h-[90vh] max-w-[95vw] flex-col overflow-hidden sm:max-w-7xl"
            >
                <div className="min-h-0 flex-1 overflow-y-auto sm:pr-4">
                    <div className="space-y-6 py-2">
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <ViewField
                                label="Payment Number"
                                value={item.payment_number}
                            />
                            <ViewField
                                label="Supplier"
                                value={item.supplier?.name}
                            />
                            <ViewField
                                label="Branch"
                                value={item.branch?.name}
                            />
                            <ViewField
                                label="Fiscal Year"
                                value={item.fiscal_year?.name}
                            />
                            <ViewField
                                label="Payment Date"
                                value={formatDateByRegionalSettings(
                                    item.payment_date,
                                )}
                            />
                            <ViewField
                                label="Payment Method"
                                value={
                                    <Badge variant="outline">
                                        {item.payment_method}
                                    </Badge>
                                }
                            />
                            <ViewField
                                label="Bank Account"
                                value={item.bank_account?.name}
                            />
                            <ViewField label="Currency" value={item.currency} />
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
                            <ViewField
                                label="Reference"
                                value={item.reference}
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
                                label="Notes"
                                value={item.notes || '-'}
                            />
                        </div>

                        <ViewModalItemsTable
                            items={item.allocations}
                            columns={allocationColumns}
                            minWidthClassName="min-w-[600px]"
                            getRowKey={(row) => row.id}
                        />
                    </div>
                </div>
            </ViewModalShell>
        );
    },
);
