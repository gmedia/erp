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

import { SupplierReturn } from '@/types/supplier-return';

interface SupplierReturnViewModalProps {
    open: boolean;
    onClose: () => void;
    item: SupplierReturn | null;
}

const ViewField = ({ label, value }: { label: string; value: React.ReactNode }) => (
    <div className="space-y-1">
        <h4 className="text-sm font-medium text-muted-foreground">{label}</h4>
        <div className="text-sm font-medium">{value || '-'}</div>
    </div>
);

export const SupplierReturnViewModal = React.memo(
    ({ item, open, onClose }: SupplierReturnViewModalProps) => {
        if (!item) return null;

        return (
            <Dialog open={open} onOpenChange={onClose}>
                <DialogContent className="max-w-4xl">
                    <DialogHeader>
                        <DialogTitle>Supplier Return Details</DialogTitle>
                        <DialogDescription>View supplier return summary and item lines</DialogDescription>
                    </DialogHeader>

                    <div className="grid grid-cols-2 gap-6 py-2">
                        <ViewField label="Return Number" value={item.return_number} />
                        <ViewField label="PO Number" value={item.purchase_order?.po_number} />
                        <ViewField label="GR Number" value={item.goods_receipt?.gr_number || '-'} />
                        <ViewField label="Supplier" value={item.supplier?.name} />
                        <ViewField label="Warehouse" value={item.warehouse?.name} />
                        <ViewField
                            label="Return Date"
                            value={item.return_date ? format(new Date(item.return_date), 'PPP') : '-'}
                        />
                        <ViewField label="Reason" value={item.reason} />
                        <ViewField label="Status" value={<Badge variant="outline">{item.status}</Badge>} />
                        <ViewField label="Notes" value={item.notes || '-'} />
                    </div>

                    <div className="space-y-2">
                        <h4 className="text-sm font-semibold">Items</h4>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b">
                                        <th className="text-left p-2">GR Item ID</th>
                                        <th className="text-left p-2">Product</th>
                                        <th className="text-left p-2">Unit</th>
                                        <th className="text-right p-2">Qty Returned</th>
                                        <th className="text-right p-2">Unit Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(item.items || []).map((it) => (
                                        <tr key={it.id} className="border-b last:border-b-0">
                                            <td className="p-2">{it.goods_receipt_item_id}</td>
                                            <td className="p-2">{it.product?.name || '-'}</td>
                                            <td className="p-2">{it.unit?.name || '-'}</td>
                                            <td className="p-2 text-right">{it.quantity_returned}</td>
                                            <td className="p-2 text-right">{it.unit_price}</td>
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
