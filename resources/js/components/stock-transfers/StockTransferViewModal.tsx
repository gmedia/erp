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
import { useViewModalDetail } from '@/hooks/useViewModalDetail';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import React from 'react';

import { useTranslation } from '@/contexts/i18n-context';
import { type StockTransfer } from '@/types/stock-transfer';

interface StockTransferViewModalProps {
    open: boolean;
    onClose: () => void;
    item: StockTransfer | null;
}

export const StockTransferViewModal = React.memo(
    ({ item, open, onClose }: StockTransferViewModalProps) => {
        const { t } = useTranslation();
        const current = useViewModalDetail({
            endpoint: '/api/stock-transfers',
            open,
            item,
        });

        if (!current) return null;

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="Stock Transfer Details"
                description={t('common.view_details')}
                contentClassName="flex max-h-[90vh] max-w-[95vw] flex-col overflow-hidden sm:max-w-7xl"
            >
                <div className="min-h-0 flex-1 overflow-y-auto sm:pr-4">
                    <div className="space-y-6 py-4">
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
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
                                value={formatDateByRegionalSettings(
                                    current.transfer_date,
                                )}
                            />
                            <ViewField
                                label="Expected Arrival"
                                value={formatDateByRegionalSettings(
                                    current.expected_arrival_date,
                                )}
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
                            <div className="text-sm font-semibold">Items</div>
                            <div className="min-w-0 rounded-md border">
                                <Table className="min-w-[760px]">
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
                                        {(current.items || []).length === 0 ? (
                                            <TableRow>
                                                <TableCell
                                                    colSpan={6}
                                                    className="py-8 text-center text-muted-foreground"
                                                >
                                                    No items.
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            (current.items || []).map((it) => (
                                                <TableRow key={it.id}>
                                                    <TableCell>
                                                        {it.product?.name ||
                                                            '-'}
                                                    </TableCell>
                                                    <TableCell>
                                                        {it.unit?.name || '-'}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {it.quantity}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {it.quantity_received}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {it.unit_cost}
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
                    </div>
                </div>
            </ViewModalShell>
        );
    },
);
