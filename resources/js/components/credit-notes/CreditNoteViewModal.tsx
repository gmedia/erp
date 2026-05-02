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

import { CreditNote, type CreditNoteItem } from '@/types/credit-note';

interface CreditNoteViewModalProps {
    open: boolean;
    onClose: () => void;
    item: CreditNote | null;
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

function createCreditNoteItemColumns(
    formatAmount: (value: FormatValueInput) => string,
): ViewModalItemsTableColumn<CreditNoteItem>[] {
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

export const CreditNoteViewModal = React.memo(
    ({ item, open, onClose }: CreditNoteViewModalProps) => {
        if (!item) return null;

        const formatAmount = (value: FormatValueInput) =>
            formatCurrencyByRegionalSettings(value ?? 0, {
                locale: 'id-ID',
                currency: 'IDR',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

        const itemColumns = createCreditNoteItemColumns(formatAmount);

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="Credit Note Details"
                description={`Credit Note ${item.credit_note_number || 'N/A'}`}
            >
                <div className="space-y-4 py-4">
                    <div className="grid grid-cols-2 gap-4">
                        <ViewField
                            label="Credit Note Number"
                            value={item.credit_note_number || '-'}
                        />
                        <ViewField
                            label="Customer"
                            value={item.customer?.name || '-'}
                        />
                        <ViewField
                            label="Customer Invoice"
                            value={item.customer_invoice?.invoice_number || '-'}
                        />
                        <ViewField
                            label="Branch"
                            value={item.branch?.name || '-'}
                        />
                        <ViewField
                            label="Fiscal Year"
                            value={item.fiscal_year?.name || '-'}
                        />
                        <ViewField
                            label="Credit Note Date"
                            value={formatDateByRegionalSettings(
                                item.credit_note_date,
                                {
                                    locale: 'id-ID',
                                },
                            )}
                        />
                        <ViewField
                            label="Reason"
                            value={
                                <Badge variant="outline">{item.reason}</Badge>
                            }
                        />
                        <ViewField
                            label="Status"
                            value={
                                <Badge variant="outline">
                                    {item.status.replace('_', ' ')}
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
                            label="Grand Total"
                            value={formatAmount(item.grand_total)}
                        />
                    </div>

                    {item.notes && (
                        <ViewField label="Notes" value={item.notes} />
                    )}

                    {item.items && item.items.length > 0 && (
                        <div className="pt-4">
                            <h3 className="mb-2 text-lg font-semibold">
                                Credit Note Items
                            </h3>
                            <ViewModalItemsTable
                                items={item.items}
                                columns={itemColumns}
                                minWidthClassName="min-w-[860px]"
                                getRowKey={(row) => row.id}
                            />
                        </div>
                    )}

                    <div className="pt-4 text-sm text-muted-foreground">
                        <div>
                            Created by: {item.created_by?.name || 'System'}
                        </div>
                        {item.confirmed_at && (
                            <div>
                                Confirmed by:{' '}
                                {item.confirmed_by?.name || 'System'} on{' '}
                                {formatDateByRegionalSettings(
                                    item.confirmed_at,
                                    {
                                        locale: 'id-ID',
                                    },
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </ViewModalShell>
        );
    },
);
