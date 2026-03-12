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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import axios from '@/lib/axios';
import { format } from 'date-fns';
import React from 'react';

import { useTranslation } from '@/contexts/i18n-context';
import { type StockTransfer } from '@/types/stock-transfer';

interface StockTransferViewModalProps {
    open: boolean;
    onClose: () => void;
    item: StockTransfer | null;
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

export const StockTransferViewModal = React.memo(
    ({ item, open, onClose }: StockTransferViewModalProps) => {
        const { t } = useTranslation();
        const [detail, setDetail] = React.useState<StockTransfer | null>(null);

        React.useEffect(() => {
            const load = async () => {
                if (!open || !item?.id) return;
                if (item.items && item.items.length > 0) {
                    setDetail(item);
                    return;
                }
                try {
                    const response = await axios.get(
                        `/api/stock-transfers/${item.id}`,
                    );
                    const data = response.data?.data ?? response.data;
                    setDetail(data);
                } catch {
                    setDetail(item);
                }
            };

            load();
        }, [open, item]);

        if (!item) return null;
        const current = detail || item;

        return (
            <Dialog open={open} onOpenChange={onClose}>
                <DialogContent className="flex max-h-[90vh] max-w-[95vw] flex-col overflow-hidden sm:max-w-7xl">
                    <DialogHeader>
                        <DialogTitle>Stock Transfer Details</DialogTitle>
                        <DialogDescription>
                            {t('common.view_details')}
                        </DialogDescription>
                    </DialogHeader>

                    <ScrollArea className="flex-1 pr-4">
                        <div className="space-y-6 py-4">
                            <div className="grid grid-cols-2 gap-6">
                                <ViewField
                                    label="Transfer Number"
                                    value={current.transfer_number}
                                />
                                <ViewField
                                    label="Status"
                                    value={
                                        <Badge variant="outline">
                                            {current.status}
                                        </Badge>
                                    }
                                />
                                <ViewField
                                    label="From Warehouse"
                                    value={current.from_warehouse?.name}
                                />
                                <ViewField
                                    label="To Warehouse"
                                    value={current.to_warehouse?.name}
                                />
                                <ViewField
                                    label="Transfer Date"
                                    value={
                                        current.transfer_date
                                            ? format(
                                                  new Date(
                                                      current.transfer_date,
                                                  ),
                                                  'PPP',
                                              )
                                            : '-'
                                    }
                                />
                                <ViewField
                                    label="Expected Arrival"
                                    value={
                                        current.expected_arrival_date
                                            ? format(
                                                  new Date(
                                                      current.expected_arrival_date,
                                                  ),
                                                  'PPP',
                                              )
                                            : '-'
                                    }
                                />
                                <ViewField
                                    label="Requested By"
                                    value={current.requested_by?.name || '-'}
                                />
                                <ViewField
                                    label="Notes"
                                    value={current.notes || '-'}
                                />
                            </div>

                            <div className="space-y-2">
                                <div className="text-sm font-semibold">
                                    Items
                                </div>
                                <div className="overflow-x-auto rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Product</TableHead>
                                                <TableHead>Unit</TableHead>
                                                <TableHead className="text-right">
                                                    Qty
                                                </TableHead>
                                                <TableHead className="text-right">
                                                    Qty Received
                                                </TableHead>
                                                <TableHead className="text-right">
                                                    Unit Cost
                                                </TableHead>
                                                <TableHead>Notes</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {(current.items || []).length ===
                                            0 ? (
                                                <TableRow>
                                                    <TableCell
                                                        colSpan={6}
                                                        className="py-8 text-center text-muted-foreground"
                                                    >
                                                        No items.
                                                    </TableCell>
                                                </TableRow>
                                            ) : (
                                                (current.items || []).map(
                                                    (it) => (
                                                        <TableRow key={it.id}>
                                                            <TableCell>
                                                                {it.product
                                                                    ?.name ||
                                                                    '-'}
                                                            </TableCell>
                                                            <TableCell>
                                                                {it.unit
                                                                    ?.name ||
                                                                    '-'}
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                {it.quantity}
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                {
                                                                    it.quantity_received
                                                                }
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                {it.unit_cost}
                                                            </TableCell>
                                                            <TableCell>
                                                                {it.notes ||
                                                                    '-'}
                                                            </TableCell>
                                                        </TableRow>
                                                    ),
                                                )
                                            )}
                                        </TableBody>
                                    </Table>
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
