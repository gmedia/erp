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

import { CustomerInvoice, type CustomerInvoiceItem } from '@/types/customer-invoice';

interface CustomerInvoiceViewModalProps {
    open: boolean;
    onClose: () => void;
    item: CustomerInvoice | null;
}

type FormatValueInput = string | number | null | undefined;

const formatQuantity = (value: FormatValueInput) =>
    formatNumberByRegionalSettings(value ?? 0, {
        locale: 'id-ID',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });

const formatPercent = (value: FormatValueInput) =>
    `${formatNumberByRegionalSettings(value ?? 0, {
        locale: 'id-ID',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })}%`;

function createCustomerInvoiceItemColumns(
    formatAmount: (value: FormatValueInput) => string,
): ViewModalItemsTableColumn<CustomerInvoiceItem>[] {
    return [
        {
            key: 'product',
            header: 'Product',
            render: (item) => item.product_name || '-',
        },
        {
            key: 'account',
            header: 'Account',
            render: (item) => item.account_name || '-',
        },
        {
            key: 'description',
            header: 'Description',
            render: (item) => item.description || '-',
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

export const CustomerInvoiceViewModal = React.memo(
    ({ item, open, onClose }: CustomerInvoiceViewModalProps) => {
        if (!item) return null;

        const formatAmount = (value: FormatValueInput) =>
            formatCurrencyByRegionalSettings(value ?? 0, {
                locale: 'id-ID',
                currency: item.currency || undefined,
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

        const itemColumns = createCustomerInvoiceItemColumns(formatAmount);

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="Customer Invoice Details"
                description={`Invoice ${item.invoice_number || 'N/A'}`}
            >
                <div className="space-y-4 py-4">
                    <div className="grid grid-cols-2 gap-4">
                        <ViewField label="Invoice Number" value={item.invoice_number || '-'} />
                        <ViewField label="Customer" value={item.customer?.name || '-'} />
                        <ViewField label="Branch" value={item.branch?.name || '-'} />
                        <ViewField label="Fiscal Year" value={item.fiscal_year?.name || '-'} />
                        <ViewField
                            label="Invoice Date"
                            value={formatDateByRegionalSettings(item.invoice_date, {
                                locale: 'id-ID',
                            })}
                        />
                        <ViewField
                            label="Due Date"
                            value={formatDateByRegionalSettings(item.due_date, {
                                locale: 'id-ID',
                            })}
                        />
                        <ViewField label="Payment Terms" value={item.payment_terms || '-'} />
                        <ViewField label="Currency" value={item.currency || '-'} />
                        <ViewField
                            label="Status"
                            value={<Badge variant="outline">{item.status.replace('_', ' ')}</Badge>}
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
                            label="Amount Received"
                            value={formatAmount(item.amount_received)}
                        />
                        <ViewField
                            label="Credit Note Amount"
                            value={formatAmount(item.credit_note_amount)}
                        />
                        <ViewField
                            label="Amount Due"
                            value={formatAmount(item.amount_due)}
                        />
                    </div>

                    {item.notes && (
                        <ViewField label="Notes" value={item.notes} />
                    )}

                    {item.items && item.items.length > 0 && (
                        <div className="pt-4">
                            <h3 className="mb-2 text-lg font-semibold">Invoice Items</h3>
                            <ViewModalItemsTable
                                items={item.items}
                                columns={itemColumns}
                                minWidthClassName="min-w-[860px]"
                                getRowKey={(row) => row.id}
                            />
                        </div>
                    )}

                    <div className="pt-4 text-sm text-muted-foreground">
                        <div>Created by: {item.created_by?.name || 'System'}</div>
                        {item.sent_at && (
                            <div>
                                Sent by: {item.sent_by?.name || 'System'} on{' '}
                                {formatDateByRegionalSettings(item.sent_at, {
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