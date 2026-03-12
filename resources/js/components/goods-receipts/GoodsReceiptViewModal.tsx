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
import { format } from 'date-fns';
import React from 'react';

import { GoodsReceipt } from '@/types/goods-receipt';

interface GoodsReceiptViewModalProps {
    open: boolean;
    onClose: () => void;
    item: GoodsReceipt | null;
}

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

export const GoodsReceiptViewModal = React.memo(
    ({ item, open, onClose }: GoodsReceiptViewModalProps) => {
        if (!item) return null;

        return (
            <Dialog open={open} onOpenChange={onClose}>
                <DialogContent className="max-w-4xl">
                    <DialogHeader>
                        <DialogTitle>Goods Receipt Details</DialogTitle>
                        <DialogDescription>
                            View GR summary and item lines
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid grid-cols-2 gap-6 py-2">
                        <ViewField label="GR Number" value={item.gr_number} />
                        <ViewField
                            label="PO Number"
                            value={item.purchase_order?.po_number}
                        />
                        <ViewField
                            label="Supplier"
                            value={item.purchase_order?.supplier?.name}
                        />
                        <ViewField
                            label="Warehouse"
                            value={item.warehouse?.name}
                        />
                        <ViewField
                            label="Receipt Date"
                            value={
                                item.receipt_date
                                    ? format(new Date(item.receipt_date), 'PPP')
                                    : '-'
                            }
                        />
                        <ViewField
                            label="Supplier Delivery Note"
                            value={item.supplier_delivery_note || '-'}
                        />
                        <ViewField
                            label="Status"
                            value={
                                <Badge variant="outline">{item.status}</Badge>
                            }
                        />
                        <ViewField
                            label="Received By"
                            value={item.received_by?.name || '-'}
                        />
                        <ViewField label="Notes" value={item.notes || '-'} />
                    </div>

                    <div className="space-y-2">
                        <h4 className="text-sm font-semibold">Items</h4>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b">
                                        <th className="p-2 text-left">
                                            PO Item ID
                                        </th>
                                        <th className="p-2 text-left">
                                            Product
                                        </th>
                                        <th className="p-2 text-left">Unit</th>
                                        <th className="p-2 text-right">
                                            Qty Received
                                        </th>
                                        <th className="p-2 text-right">
                                            Qty Accepted
                                        </th>
                                        <th className="p-2 text-right">
                                            Qty Rejected
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
                                                {it.purchase_order_item_id}
                                            </td>
                                            <td className="p-2">
                                                {it.product?.name || '-'}
                                            </td>
                                            <td className="p-2">
                                                {it.unit?.name || '-'}
                                            </td>
                                            <td className="p-2 text-right">
                                                {it.quantity_received}
                                            </td>
                                            <td className="p-2 text-right">
                                                {it.quantity_accepted}
                                            </td>
                                            <td className="p-2 text-right">
                                                {it.quantity_rejected}
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
