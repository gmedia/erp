'use client';

import { type StatusActionConfig } from '@/components/common/StatusActionDialog';
import {
    type RowActionHandlers,
    StatusRowActions,
} from '@/components/common/StatusRowActions';
import { useEntityStatusAction } from '@/hooks/useEntityStatusAction';
import { usePermissions } from '@/hooks/usePermissions';
import { type GoodsReceipt } from '@/types/goods-receipt';

type GoodsReceiptAction = 'cancel';

export function GoodsReceiptRowActions(
    props: Readonly<RowActionHandlers<GoodsReceipt>>,
) {
    const { can } = usePermissions();
    const { status, gr_number: grNumber } = props.item;
    const { run, processing } = useEntityStatusAction<
        GoodsReceipt,
        GoodsReceiptAction
    >({
        endpoint: '/api/goods-receipts',
        queryKey: 'goods-receipts',
        successMessages: { cancel: 'Goods receipt cancelled.' },
        failedMessage: 'Failed to cancel goods receipt.',
        request: (_action, id) => ({
            method: 'put',
            url: `/api/goods-receipts/${id}`,
            body: { status: 'cancelled' },
        }),
    });

    const actions: StatusActionConfig<GoodsReceiptAction>[] = [];
    if (can('goods_receipt.edit') && status === 'confirmed') {
        actions.push({
            action: 'cancel',
            label: 'Cancel Goods Receipt',
            title: 'Cancel this goods receipt?',
            description: `Goods receipt ${grNumber ?? ''} will be cancelled, its stock and journal reversed.`,
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
