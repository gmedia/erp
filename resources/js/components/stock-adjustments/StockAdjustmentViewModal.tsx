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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { format } from 'date-fns';
import React from 'react';
import axios from '@/lib/axios';

import { type StockAdjustment } from '@/types/stock-adjustment';
import { useTranslation } from '@/contexts/i18n-context';

interface StockAdjustmentViewModalProps {
    open: boolean;
    onClose: () => void;
    item: StockAdjustment | null;
}

const ViewField = ({ label, value }: { label: string; value: React.ReactNode }) => (
    <div className="space-y-1">
        <h4 className="text-sm font-medium text-muted-foreground">{label}</h4>
        <div className="text-sm font-medium">{value || '-'}</div>
    </div>
);

export const StockAdjustmentViewModal = React.memo(
    ({ item, open, onClose }: StockAdjustmentViewModalProps) => {
        const { t } = useTranslation();
        const [detail, setDetail] = React.useState<StockAdjustment | null>(null);

        React.useEffect(() => {
            const load = async () => {
                if (!open || !item?.id) return;
                if (item.items && item.items.length > 0) {
                    setDetail(item);
                    return;
                }
                try {
                    const response = await axios.get(`/api/stock-adjustments/${item.id}`);
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
                <DialogContent className="max-w-[95vw] sm:max-w-7xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Stock Adjustment Details</DialogTitle>
                        <DialogDescription>{t('common.view_details')}</DialogDescription>
                    </DialogHeader>

                    <div className="grid grid-cols-2 gap-6 py-4">
                        <ViewField label="Adjustment Number" value={current.adjustment_number} />
                        <ViewField label="Status" value={<Badge variant="outline">{current.status}</Badge>} />
                        <ViewField label="Adjustment Type" value={<Badge variant="outline">{current.adjustment_type}</Badge>} />
                        <ViewField label="Warehouse" value={current.warehouse?.name} />
                        <ViewField
                            label="Adjustment Date"
                            value={current.adjustment_date ? format(new Date(current.adjustment_date), 'PPP') : '-'}
                        />
                        <ViewField label="Stocktake" value={current.inventory_stocktake?.stocktake_number || '-'} />
                        <ViewField label="Notes" value={current.notes || '-'} />
                    </div>

                    <div className="space-y-2">
                        <div className="text-sm font-semibold">Items</div>
                        <div className="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Product</TableHead>
                                        <TableHead>Unit</TableHead>
                                        <TableHead className="text-right">Qty Before</TableHead>
                                        <TableHead className="text-right">Qty Adjusted</TableHead>
                                        <TableHead className="text-right">Qty After</TableHead>
                                        <TableHead className="text-right">Unit Cost</TableHead>
                                        <TableHead className="text-right">Total Cost</TableHead>
                                        <TableHead>Reason</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {(current.items || []).length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={8} className="text-center text-muted-foreground py-8">
                                                No items.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        (current.items || []).map((it) => (
                                            <TableRow key={it.id}>
                                                <TableCell>{it.product?.name || '-'}</TableCell>
                                                <TableCell>{it.unit?.name || '-'}</TableCell>
                                                <TableCell className="text-right">{it.quantity_before}</TableCell>
                                                <TableCell className="text-right">{it.quantity_adjusted}</TableCell>
                                                <TableCell className="text-right">{it.quantity_after}</TableCell>
                                                <TableCell className="text-right">{it.unit_cost}</TableCell>
                                                <TableCell className="text-right">{it.total_cost}</TableCell>
                                                <TableCell>{it.reason || '-'}</TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
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
