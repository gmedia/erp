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
import { type InventoryStocktake } from '@/types/inventory-stocktake';

interface InventoryStocktakeViewModalProps {
    open: boolean;
    onClose: () => void;
    item: InventoryStocktake | null;
}

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
                <DialogContent className="flex max-h-[90vh] max-w-[95vw] flex-col overflow-hidden sm:max-w-7xl">
                    <DialogHeader>
                        <DialogTitle>Inventory Stocktake Details</DialogTitle>
                        <DialogDescription>
                            {t('common.view_details')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="min-h-0 flex-1 overflow-y-auto sm:pr-4">
                        <div className="space-y-6 py-4">
                            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
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
                                    value={
                                        current.product_category?.name || '-'
                                    }
                                />
                                <ViewField
                                    label="Stocktake Date"
                                    value={formatDateByRegionalSettings(
                                        current.stocktake_date,
                                    )}
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
                                <div className="min-w-0 rounded-md border">
                                    <Table className="min-w-[820px]">
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
                                            {(current.items || []).length ===
                                            0 ? (
                                                <TableRow>
                                                    <TableCell
                                                        colSpan={7}
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
                                                                    it.system_quantity
                                                                }
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                {it.counted_quantity ??
                                                                    '-'}
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                {it.variance ??
                                                                    '-'}
                                                            </TableCell>
                                                            <TableCell>
                                                                <Badge variant="outline">
                                                                    {it.result}
                                                                </Badge>
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
