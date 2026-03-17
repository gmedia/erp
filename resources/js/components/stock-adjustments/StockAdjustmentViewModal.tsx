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
import { formatDateByRegionalSettings } from '@/utils/date-format';
import React from 'react';

import { useTranslation } from '@/contexts/i18n-context';
import { type StockAdjustment } from '@/types/stock-adjustment';

interface StockAdjustmentViewModalProps {
    open: boolean;
    onClose: () => void;
    item: StockAdjustment | null;
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

export const StockAdjustmentViewModal = React.memo(
    ({ item, open, onClose }: StockAdjustmentViewModalProps) => {
        const { t } = useTranslation();
        const [detail, setDetail] = React.useState<StockAdjustment | null>(
            null,
        );

        React.useEffect(() => {
            const load = async () => {
                if (!open || !item?.id) return;
                if (item.items && item.items.length > 0) {
                    setDetail(item);
                    return;
                }
                try {
                    const response = await axios.get(
                        `/api/stock-adjustments/${item.id}`,
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
                        <DialogTitle>Stock Adjustment Details</DialogTitle>
                        <DialogDescription>
                            {t('common.view_details')}
                        </DialogDescription>
                    </DialogHeader>

                    <ScrollArea className="flex-1 pr-4">
                        <div className="space-y-6 py-4">
                            <div className="grid grid-cols-2 gap-6">
                                <ViewField
                                    label="Adjustment Number"
                                    value={current.adjustment_number}
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
                                    label="Adjustment Type"
                                    value={
                                        <Badge variant="outline">
                                            {current.adjustment_type}
                                        </Badge>
                                    }
                                />
                                <ViewField
                                    label="Warehouse"
                                    value={current.warehouse?.name}
                                />
                                <ViewField
                                    label="Adjustment Date"
                                    value={formatDateByRegionalSettings(
                                        current.adjustment_date,
                                    )}
                                />
                                <ViewField
                                    label="Stocktake"
                                    value={
                                        current.inventory_stocktake
                                            ?.stocktake_number || '-'
                                    }
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
                                                    Qty Before
                                                </TableHead>
                                                <TableHead className="text-right">
                                                    Qty Adjusted
                                                </TableHead>
                                                <TableHead className="text-right">
                                                    Qty After
                                                </TableHead>
                                                <TableHead className="text-right">
                                                    Unit Cost
                                                </TableHead>
                                                <TableHead className="text-right">
                                                    Total Cost
                                                </TableHead>
                                                <TableHead>Reason</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {(current.items || []).length ===
                                            0 ? (
                                                <TableRow>
                                                    <TableCell
                                                        colSpan={8}
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
                                                                {
                                                                    it.quantity_before
                                                                }
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                {
                                                                    it.quantity_adjusted
                                                                }
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                {
                                                                    it.quantity_after
                                                                }
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                {it.unit_cost}
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                {it.total_cost}
                                                            </TableCell>
                                                            <TableCell>
                                                                {it.reason ||
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
