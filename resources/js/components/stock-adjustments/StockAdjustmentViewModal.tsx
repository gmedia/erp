import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Badge } from '@/components/ui/badge';
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
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="Stock Adjustment Details"
                description={t('common.view_details')}
                contentClassName="flex max-h-[90vh] max-w-[95vw] flex-col overflow-hidden sm:max-w-7xl"
            >
                <div className="min-h-0 flex-1 overflow-y-auto sm:pr-4">
                    <div className="space-y-6 py-4">
                            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
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
                                <div className="min-w-0 rounded-md border">
                                    <Table className="min-w-[920px]">
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
                </div>
            </ViewModalShell>
        );
    },
);
