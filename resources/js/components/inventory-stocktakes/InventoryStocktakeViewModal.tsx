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
import { type InventoryStocktake } from '@/types/inventory-stocktake';

interface InventoryStocktakeViewModalProps {
    open: boolean;
    onClose: () => void;
    item: InventoryStocktake | null;
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

export const InventoryStocktakeViewModal = React.memo(
    ({ item, open, onClose }: InventoryStocktakeViewModalProps) => {
        const { t } = useTranslation();
        const [detail, setDetail] = React.useState<InventoryStocktake | null>(
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
                        `/api/inventory-stocktakes/${item.id}`,
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
                <DialogContent className="max-h-[90vh] max-w-[95vw] overflow-y-auto sm:max-w-7xl">
                    <DialogHeader>
                        <DialogTitle>Inventory Stocktake Details</DialogTitle>
                        <DialogDescription>
                            {t('common.view_details')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid grid-cols-2 gap-6 py-4">
                        <ViewField
                            label="Stocktake Number"
                            value={current.stocktake_number}
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
                            label="Warehouse"
                            value={current.warehouse?.name}
                        />
                        <ViewField
                            label="Product Category"
                            value={current.product_category?.name || '-'}
                        />
                        <ViewField
                            label="Stocktake Date"
                            value={
                                current.stocktake_date
                                    ? format(
                                          new Date(current.stocktake_date),
                                          'PPP',
                                      )
                                    : '-'
                            }
                        />
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
                                        <TableHead className="text-right">
                                            System Qty
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Counted Qty
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Variance
                                        </TableHead>
                                        <TableHead>Result</TableHead>
                                        <TableHead>Notes</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {(current.items || []).length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={7}
                                                className="py-8 text-center text-muted-foreground"
                                            >
                                                No items.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        (current.items || []).map((it) => (
                                            <TableRow key={it.id}>
                                                <TableCell>
                                                    {it.product?.name || '-'}
                                                </TableCell>
                                                <TableCell>
                                                    {it.unit?.name || '-'}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {it.system_quantity}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {it.counted_quantity ?? '-'}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {it.variance ?? '-'}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline">
                                                        {it.result}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    {it.notes || '-'}
                                                </TableCell>
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
