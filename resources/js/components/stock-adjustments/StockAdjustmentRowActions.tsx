'use client';

import { type StatusActionConfig } from '@/components/common/StatusActionDialog';
import {
    type RowActionHandlers,
    StatusRowActions,
} from '@/components/common/StatusRowActions';
import { useEntityStatusAction } from '@/hooks/useEntityStatusAction';
import { usePermissions } from '@/hooks/usePermissions';
import { type StockAdjustment } from '@/types/stock-adjustment';

type StockAdjustmentAction = 'cancel';

/** Issue #375 (review #426 butir 7): pembatalan adjustment approved membalik stok dan jurnal. */
export function StockAdjustmentRowActions(
    props: Readonly<RowActionHandlers<StockAdjustment>>,
) {
    const { can } = usePermissions();
    const { status, adjustment_number: adjustmentNumber } = props.item;
    const { run, processing } = useEntityStatusAction<
        StockAdjustment,
        StockAdjustmentAction
    >({
        endpoint: '/api/stock-adjustments',
        queryKey: 'stock-adjustments',
        successMessages: { cancel: 'Stock adjustment cancelled.' },
        failedMessage: 'Failed to cancel stock adjustment.',
        request: (_action, id) => ({
            method: 'put',
            url: `/api/stock-adjustments/${id}`,
            body: { status: 'cancelled' },
        }),
    });

    const actions: StatusActionConfig<StockAdjustmentAction>[] = [];
    if (can('stock_adjustment.edit') && status === 'approved') {
        actions.push({
            action: 'cancel',
            label: 'Cancel Stock Adjustment',
            title: 'Cancel this stock adjustment?',
            description:
                `Stock adjustment ${adjustmentNumber ?? ''} will be cancelled, ` +
                'its stock movements and journal reversed.',
            confirmLabel: 'Yes, cancel',
        });
    }

    return (
        <StatusRowActions
            {...props}
            actions={actions}
            run={run}
            processing={processing}
            processingLabel="Cancelling..."
        />
    );
}
