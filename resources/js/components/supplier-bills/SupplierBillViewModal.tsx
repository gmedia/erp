import { ViewField } from '@/components/common/ViewField';
import {
    ViewModalItemsTable,
    type ViewModalItemsTableColumn,
} from '@/components/common/ViewModalItemsTable';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import {
    createAmountFormatter,
    formatPercent,
    formatQuantity,
    type FormatValueInput,
} from '@/components/common/report-format-helpers';
import { Badge } from '@/components/ui/badge';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import React from 'react';

import { SupplierBill, type SupplierBillItem } from '@/types/supplier-bill';

interface SupplierBillViewModalProps {
    open: boolean;
    onClose: () => void;
    item: SupplierBill | null;
}

function createSupplierBillItemColumns(
    formatAmount: (value: FormatValueInput) => string,
): ViewModalItemsTableColumn<SupplierBillItem>[] {
    return [
        {
            key: 'description',
            header: 'Description',
            render: (item) => item.description || '-',
        },
        {
            key: 'account',
            header: 'Account',
            render: (item) => item.account_name || '-',
        },
        {
            key: 'quantity',
            header: 'Qty',
            align: 'right',
            render: (item) => formatQuantity(item.quantity),
        },
        {
            key: 'unit_price',
            header: 'Unit Price',
            align: 'right',
            render: (item) => formatAmount(item.unit_price),
        },
        {
            key: 'discount_percent',
            header: 'Disc %',
            align: 'right',
            render: (item) => formatPercent(item.discount_percent),
        },
        {
            key: 'tax_percent',
            header: 'Tax %',
            align: 'right',
            render: (item) => formatPercent(item.tax_percent),
        },
        {
            key: 'line_total',
            header: 'Line Total',
            align: 'right',
            render: (item) => formatAmount(item.line_total),
        },
    ];
}

export const SupplierBillViewModal = React.memo(
    ({ item, open, onClose }: SupplierBillViewModalProps) => {
        if (!item) return null;

        const formatAmount = createAmountFormatter(item.currency);

        const itemColumns = createSupplierBillItemColumns(formatAmount);

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="Supplier Bill Details"
                description="View bill summary and items"
                contentClassName="flex max-h-[90vh] max-w-[95vw] flex-col overflow-hidden sm:max-w-7xl"
            >
                <div className="min-h-0 flex-1 overflow-y-auto sm:pr-4">
                    <div className="space-y-6 py-2">
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <ViewField
                                label="Bill Number"
                                value={item.bill_number}
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
                                label="Purchase Order"
                                value={item.purchase_order?.po_number}
                            />
                            <ViewField
                                label="Goods Receipt"
                                value={item.goods_receipt?.gr_number}
                            />
                            <ViewField
                                label="Supplier Invoice Number"
                                value={item.supplier_invoice_number}
                            />
                            <ViewField
                                label="Supplier Invoice Date"
                                value={formatDateByRegionalSettings(
                                    item.supplier_invoice_date,
                                )}
                            />
                            <ViewField
                                label="Bill Date"
                                value={formatDateByRegionalSettings(
                                    item.bill_date,
                                )}
                            />
                            <ViewField
                                label="Due Date"
                                value={formatDateByRegionalSettings(
                                    item.due_date,
                                )}
                            />
                            <ViewField
                                label="Payment Terms"
                                value={item.payment_terms}
                            />
                            <ViewField label="Currency" value={item.currency} />
                            <ViewField
                                label="Status"
                                value={
                                    <Badge variant="outline">
                                        {item.status}
                                    </Badge>
                                }
                            />
                            <ViewField
                                label="Subtotal"
                                value={formatAmount(item.subtotal)}
                            />
                            <ViewField
                                label="Tax Amount"
                                value={formatAmount(item.tax_amount)}
                            />
                            <ViewField
                                label="Discount Amount"
                                value={formatAmount(item.discount_amount)}
                            />
                            <ViewField
                                label="Grand Total"
                                value={formatAmount(item.grand_total)}
                            />
                            <ViewField
                                label="Amount Paid"
                                value={formatAmount(item.amount_paid)}
                            />
                            <ViewField
                                label="Amount Due"
                                value={formatAmount(item.amount_due)}
                            />
                            <ViewField
                                label="Notes"
                                value={item.notes || '-'}
                            />
                        </div>

                        <ViewModalItemsTable
                            items={item.items}
                            columns={itemColumns}
                            minWidthClassName="min-w-[860px]"
                            getRowKey={(row) => row.id}
                        />
                    </div>
                </div>
            </ViewModalShell>
        );
    },
);
