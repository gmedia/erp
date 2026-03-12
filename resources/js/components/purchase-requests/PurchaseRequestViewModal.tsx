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
import { format } from 'date-fns';
import React from 'react';

import { PurchaseRequest } from '@/types/purchase-request';

interface PurchaseRequestViewModalProps {
    open: boolean;
    onClose: () => void;
    item: PurchaseRequest | null;
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

export const PurchaseRequestViewModal = React.memo(
    ({ item, open, onClose }: PurchaseRequestViewModalProps) => {
        if (!item) return null;

        return (
            <Dialog open={open} onOpenChange={onClose}>
                <DialogContent className="flex max-h-[90vh] max-w-[95vw] flex-col overflow-hidden sm:max-w-7xl">
                    <DialogHeader>
                        <DialogTitle>Purchase Request Details</DialogTitle>
                        <DialogDescription>
                            View details and requested items
                        </DialogDescription>
                    </DialogHeader>

                    <ScrollArea className="flex-1 pr-4">
                        <div className="space-y-6 py-2">
                            <div className="grid grid-cols-2 gap-6">
                                <ViewField
                                    label="PR Number"
                                    value={item.pr_number}
                                />
                                <ViewField
                                    label="Branch"
                                    value={item.branch?.name}
                                />
                                <ViewField
                                    label="Department"
                                    value={item.department?.name}
                                />
                                <ViewField
                                    label="Requested By"
                                    value={item.requester?.name}
                                />
                                <ViewField
                                    label="Request Date"
                                    value={
                                        item.request_date
                                            ? format(
                                                  new Date(item.request_date),
                                                  'PPP',
                                              )
                                            : '-'
                                    }
                                />
                                <ViewField
                                    label="Required Date"
                                    value={
                                        item.required_date
                                            ? format(
                                                  new Date(item.required_date),
                                                  'PPP',
                                              )
                                            : '-'
                                    }
                                />
                                <ViewField
                                    label="Priority"
                                    value={<Badge>{item.priority}</Badge>}
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
                                    label="Estimated Amount"
                                    value={item.estimated_amount ?? '0'}
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
                                                    Quantity
                                                </th>
                                                <th className="p-2 text-right">
                                                    Est. Unit Price
                                                </th>
                                                <th className="p-2 text-right">
                                                    Est. Total
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
                                                        {it.quantity}
                                                    </td>
                                                    <td className="p-2 text-right">
                                                        {it.estimated_unit_price ||
                                                            '0'}
                                                    </td>
                                                    <td className="p-2 text-right">
                                                        {it.estimated_total ||
                                                            '0'}
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
