import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { format } from 'date-fns';
import React from 'react';

import { PurchaseOrder } from '@/types/purchase-order';

interface PurchaseOrderViewModalProps {
    open: boolean;
    onClose: () => void;
    item: PurchaseOrder | null;
}

const ViewField = ({ label, value }: { label: string; value: React.ReactNode }) => (
    <div className="space-y-1">
        <h4 className="text-sm font-medium text-muted-foreground">{label}</h4>
        <div className="text-sm font-medium">{value || '-'}</div>
    </div>
);

export const PurchaseOrderViewModal = React.memo(
    ({ item, open, onClose }: PurchaseOrderViewModalProps) => {
        if (!item) return null;

        return (
            <Dialog open={open} onOpenChange={onClose}>
                <DialogContent className="max-w-4xl">
                    <DialogHeader>
                        <DialogTitle>Purchase Order Details</DialogTitle>
                        <DialogDescription>View PO summary and ordered items</DialogDescription>
                    </DialogHeader>

                    <div className="grid grid-cols-2 gap-6 py-2">
                        <ViewField label="PO Number" value={item.po_number} />
                        <ViewField label="Supplier" value={item.supplier?.name} />
                        <ViewField label="Warehouse" value={item.warehouse?.name} />
                        <ViewField
                            label="Order Date"
                            value={item.order_date ? format(new Date(item.order_date), 'PPP') : '-'}
                        />
                        <ViewField
                            label="Expected Delivery"
                            value={item.expected_delivery_date ? format(new Date(item.expected_delivery_date), 'PPP') : '-'}
                        />
                        <ViewField label="Payment Terms" value={item.payment_terms} />
                        <ViewField label="Currency" value={item.currency} />
                        <ViewField label="Status" value={<Badge variant="outline">{item.status}</Badge>} />
                        <ViewField label="Subtotal" value={item.subtotal} />
                        <ViewField label="Tax Amount" value={item.tax_amount} />
                        <ViewField label="Discount Amount" value={item.discount_amount} />
                        <ViewField label="Grand Total" value={item.grand_total} />
                        <ViewField label="Shipping Address" value={item.shipping_address || '-'} />
                        <ViewField label="Notes" value={item.notes || '-'} />
                    </div>

                    <div className="space-y-2">
                        <h4 className="text-sm font-semibold">Items</h4>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b">
                                        <th className="text-left p-2">Product</th>
                                        <th className="text-left p-2">Unit</th>
                                        <th className="text-right p-2">Qty</th>
                                        <th className="text-right p-2">Unit Price</th>
                                        <th className="text-right p-2">Disc %</th>
                                        <th className="text-right p-2">Tax %</th>
                                        <th className="text-right p-2">Line Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(item.items || []).map((it) => (
                                        <tr key={it.id} className="border-b last:border-b-0">
                                            <td className="p-2">{it.product?.name || '-'}</td>
                                            <td className="p-2">{it.unit?.name || '-'}</td>
                                            <td className="p-2 text-right">{it.quantity}</td>
                                            <td className="p-2 text-right">{it.unit_price}</td>
                                            <td className="p-2 text-right">{it.discount_percent}</td>
                                            <td className="p-2 text-right">{it.tax_percent}</td>
                                            <td className="p-2 text-right">{it.line_total}</td>
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
