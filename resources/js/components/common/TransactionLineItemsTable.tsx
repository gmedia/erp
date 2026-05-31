'use client';

import {
    EntityFormItemActionsCell,
    EntityFormItemEmptyRow,
} from '@/components/common/EntityFormItemTable';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    formatCurrencyByRegionalSettings,
    formatNumberByRegionalSettings,
} from '@/utils/number-format';

export interface TransactionLineItem {
    id?: string;
    product_label?: string;
    account_label?: string;
    description?: string;
    quantity: number;
    unit_price: number;
    discount_percent?: number;
    tax_percent?: number;
}

export interface TransactionLineColumn {
    key: string;
    label: string;
}

export interface TransactionLineItemsTableProps<T extends TransactionLineItem> {
    items: T[];
    includeDiscount?: boolean;
    currency?: string;
    onEdit: (index: number) => void;
    onRemove: (index: number) => void;
    emptyMessage?: string;
}

function computeLineTotal(item: TransactionLineItem, includeDiscount: boolean) {
    const discountFactor = includeDiscount
        ? 1 - (item.discount_percent ?? 0) / 100
        : 1;
    return (
        item.quantity *
        item.unit_price *
        discountFactor *
        (1 + (item.tax_percent ?? 0) / 100)
    );
}

function formatQty(value: number) {
    return formatNumberByRegionalSettings(value, {
        locale: 'id-ID',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function formatCurrency(value: number, currency?: string) {
    return formatCurrencyByRegionalSettings(value, {
        locale: 'id-ID',
        currency: currency || 'IDR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

export function TransactionLineItemsTable<T extends TransactionLineItem>({
    items,
    includeDiscount = false,
    currency,
    onEdit,
    onRemove,
    emptyMessage = "No items added yet. Click 'Add Item' to start.",
}: Readonly<TransactionLineItemsTableProps<T>>) {
    const colSpan = includeDiscount ? 9 : 8;

    if (items.length === 0) {
        return (
            <Table>
                <TableBody>
                    <EntityFormItemEmptyRow
                        colSpan={colSpan}
                        message={emptyMessage}
                    />
                </TableBody>
            </Table>
        );
    }

    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead className="w-12">#</TableHead>
                    <TableHead>Product/Account</TableHead>
                    <TableHead>Description</TableHead>
                    <TableHead className="text-right">Qty</TableHead>
                    <TableHead className="text-right">Unit Price</TableHead>
                    {includeDiscount && (
                        <TableHead className="text-right">Discount %</TableHead>
                    )}
                    <TableHead className="text-right">Tax %</TableHead>
                    <TableHead className="text-right">Line Total</TableHead>
                    <TableHead className="w-20">Actions</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {items.map((field, index) => {
                    const lineTotal = computeLineTotal(field, includeDiscount);
                    return (
                        <TableRow key={field.id ?? index}>
                            <TableCell className="font-mono text-xs text-muted-foreground">
                                {String(index + 1)}
                            </TableCell>
                            <TableCell>
                                <div className="space-y-1">
                                    <div className="font-medium">
                                        {field.product_label || 'No Product'}
                                    </div>
                                    <div className="text-xs text-muted-foreground">
                                        {field.account_label || 'No Account'}
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>{field.description || '-'}</TableCell>
                            <TableCell className="text-right">
                                {formatQty(field.quantity)}
                            </TableCell>
                            <TableCell className="text-right">
                                {formatCurrency(field.unit_price, currency)}
                            </TableCell>
                            {includeDiscount && (
                                <TableCell className="text-right">
                                    {formatQty(field.discount_percent ?? 0)}%
                                </TableCell>
                            )}
                            <TableCell className="text-right">
                                {formatQty(field.tax_percent ?? 0)}%
                            </TableCell>
                            <TableCell className="text-right font-medium">
                                {formatCurrency(lineTotal, currency)}
                            </TableCell>
                            <EntityFormItemActionsCell
                                index={index}
                                onEdit={onEdit}
                                onRemove={onRemove}
                            />
                        </TableRow>
                    );
                })}
            </TableBody>
        </Table>
    );
}
