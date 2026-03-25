import { ViewField } from '@/components/common/ViewField';
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
import { formatDateByRegionalSettings } from '@/utils/date-format';
import React from 'react';

import { SupplierReturn } from '@/types/supplier-return';

interface SupplierReturnViewModalProps {
    open: boolean;
    onClose: () => void;
    item: SupplierReturn | null;
}

export const SupplierReturnViewModal = React.memo(
    ({ item, open, onClose }: SupplierReturnViewModalProps) => {
        if (!item) return null;

        return (
            <Dialog open={open} onOpenChange={onClose}>
                <DialogContent className="flex max-h-[90vh] max-w-[95vw] flex-col overflow-hidden sm:max-w-7xl">
                    <DialogHeader>
                        <DialogTitle>Supplier Return Details</DialogTitle>
                        <DialogDescription>
                            View supplier return summary and item lines
                        </DialogDescription>
                    </DialogHeader>

                    <div className="min-h-0 flex-1 overflow-y-auto sm:pr-4">
                        <div className="space-y-6 py-2">
                            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <ViewField
                                    label="Return Number"
                                    value={item.return_number}
                                />
                                <ViewField
                                    label="PO Number"
                                    value={item.purchase_order?.po_number}
                                />
                                <ViewField
                                    label="GR Number"
                                    value={item.goods_receipt?.gr_number || '-'}
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
                                    label="Return Date"
                                    value={formatDateByRegionalSettings(
                                        item.return_date,
                                    )}
                                />
                                <ViewField label="Reason" value={item.reason} />
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

                            <div className="space-y-2">
                                <h4 className="text-sm font-semibold">Items</h4>
                                <div className="overflow-x-auto rounded-md border">
                                    <table className="min-w-[720px] text-sm">
                                        <thead>
                                            <tr className="border-b">
                                                <th className="p-2 text-left">
                                                    GR Item ID
                                                </th>
                                                <th className="p-2 text-left">
                                                    Product
                                                </th>
                                                <th className="p-2 text-left">
                                                    Unit
                                                </th>
                                                <th className="p-2 text-right">
                                                    Qty Returned
                                                </th>
                                                <th className="p-2 text-right">
                                                    Unit Price
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
                                                        {
                                                            it.goods_receipt_item_id
                                                        }
                                                    </td>
                                                    <td className="p-2">
                                                        {it.product?.name ||
                                                            '-'}
                                                    </td>
                                                    <td className="p-2">
                                                        {it.unit?.name || '-'}
                                                    </td>
                                                    <td className="p-2 text-right">
                                                        {it.quantity_returned}
                                                    </td>
                                                    <td className="p-2 text-right">
                                                        {it.unit_price}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

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
