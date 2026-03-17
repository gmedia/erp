import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    formatCurrencyByRegionalSettings,
    formatNumberByRegionalSettings,
} from '@/utils/number-format';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import React from 'react';

import { PurchaseOrder } from '@/types/purchase-order';

interface PurchaseOrderViewModalProps {
    open: boolean;
    onClose: () => void;
    item: PurchaseOrder | null;
}

type FormatValueInput = string | number | null | undefined;

const ViewField = ({
    label,
    value,
}: {
    label: string;
    value: React.ReactNode;
}) => (
    <div className="space-y-1">
        <h4 className="text-sm font-medium text-muted-foreground">{label}</h4>
        <div className="text-sm font-medium">{value || '-'}</div>
    </div>
);

export const PurchaseOrderViewModal = React.memo(
    ({ item, open, onClose }: PurchaseOrderViewModalProps) => {
        if (!item) return null;

        const formatAmount = (value: FormatValueInput) =>
            formatCurrencyByRegionalSettings(value ?? 0, {
                locale: 'id-ID',
                currency: item.currency || undefined,
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

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

        return (
            <Dialog open={open} onOpenChange={onClose}>
                <DialogContent className="flex max-h-[90vh] max-w-[95vw] flex-col overflow-hidden sm:max-w-7xl">
                    <DialogHeader>
                        <DialogTitle>Purchase Order Details</DialogTitle>
                        <DialogDescription>
                            View PO summary and ordered items
                        </DialogDescription>
                    </DialogHeader>

                    <ScrollArea className="flex-1 pr-4">
                        <div className="space-y-6 py-2">
                            <div className="grid grid-cols-2 gap-6">
                                <ViewField
                                    label="PO Number"
                                    value={item.po_number}
                                />
                                <ViewField
                                    label="Supplier"
                                    value={item.supplier?.name}
                                />
                                <ViewField
                                    label="Warehouse"
                                    value={item.warehouse?.name}
                                />
                                <ViewField
                                    label="Order Date"
                                    value={formatDateByRegionalSettings(
                                        item.order_date,
                                    )}
                                />
                                <ViewField
                                    label="Expected Delivery"
                                    value={formatDateByRegionalSettings(
                                        item.expected_delivery_date,
                                    )}
                                />
                                <ViewField
                                    label="Payment Terms"
                                    value={item.payment_terms}
                                />
                                <ViewField
                                    label="Currency"
                                    value={item.currency}
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
                                    label="Shipping Address"
                                    value={item.shipping_address || '-'}
                                />
                                <ViewField
                                    label="Notes"
                                    value={item.notes || '-'}
                                />
                            </div>

                            <div className="space-y-2">
                                <h4 className="text-sm font-semibold">Items</h4>
                                <div className="overflow-x-auto rounded-md border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b">
                                                <th className="p-2 text-left">
                                                    Product
                                                </th>
                                                <th className="p-2 text-left">
                                                    Unit
                                                </th>
                                                <th className="p-2 text-right">
                                                    Qty
                                                </th>
                                                <th className="p-2 text-right">
                                                    Unit Price
                                                </th>
                                                <th className="p-2 text-right">
                                                    Disc %
                                                </th>
                                                <th className="p-2 text-right">
                                                    Tax %
                                                </th>
                                                <th className="p-2 text-right">
                                                    Line Total
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {(item.items || []).map((it) => (
                                                <tr
                                                    key={it.id}
                                                    className="border-b last:border-b-0"
                                                >
                                                    <td className="p-2">
                                                        {it.product?.name ||
                                                            '-'}
                                                    </td>
                                                    <td className="p-2">
                                                        {it.unit?.name || '-'}
                                                    </td>
                                                    <td className="p-2 text-right">
                                                        {formatQuantity(
                                                            it.quantity,
                                                        )}
                                                    </td>
                                                    <td className="p-2 text-right">
                                                        {formatAmount(
                                                            it.unit_price,
                                                        )}
                                                    </td>
                                                    <td className="p-2 text-right">
                                                        {formatPercent(
                                                            it.discount_percent,
                                                        )}
                                                    </td>
                                                    <td className="p-2 text-right">
                                                        {formatPercent(
                                                            it.tax_percent,
                                                        )}
                                                    </td>
                                                    <td className="p-2 text-right">
                                                        {formatAmount(
                                                            it.line_total,
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </ScrollArea>

                    <DialogFooter>
                        <Button type="button" onClick={onClose}>
                            Close
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        );
    },
);
