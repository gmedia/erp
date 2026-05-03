import { ViewField } from '@/components/common/ViewField';
import {
    ViewModalItemsTable,
    type ViewModalItemsTableColumn,
} from '@/components/common/ViewModalItemsTable';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import {
    createAmountFormatter,
    createPricingColumns,
} from '@/components/common/report-format-helpers';
import { Badge } from '@/components/ui/badge';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import React from 'react';

import { PurchaseOrder, type PurchaseOrderItem } from '@/types/purchase-order';

interface PurchaseOrderViewModalProps {
    open: boolean;
    onClose: () => void;
    item: PurchaseOrder | null;
}

function createPurchaseOrderItemColumns(
    formatAmount: (value: string | number | null | undefined) => string,
): ViewModalItemsTableColumn<PurchaseOrderItem>[] {
    return [
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
        ...createPricingColumns<PurchaseOrderItem>(formatAmount),
    ];
}

export const PurchaseOrderViewModal = React.memo(
    ({ item, open, onClose }: PurchaseOrderViewModalProps) => {
        if (!item) return null;

        const formatAmount = createAmountFormatter(item.currency);

        const itemColumns = createPurchaseOrderItemColumns(formatAmount);

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="Purchase Order Details"
                description="View PO summary and ordered items"
                contentClassName="flex max-h-[90vh] max-w-[95vw] flex-col overflow-hidden sm:max-w-7xl"
            >
                <div className="min-h-0 flex-1 overflow-y-auto sm:pr-4">
                    <div className="space-y-6 py-2">
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
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
                                label="Shipping Address"
                                value={item.shipping_address || '-'}
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
