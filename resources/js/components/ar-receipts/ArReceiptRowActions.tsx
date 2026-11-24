'use client';

import { type StatusActionConfig } from '@/components/common/StatusActionDialog';
import {
    type RowActionHandlers,
    StatusRowActions,
} from '@/components/common/StatusRowActions';
import { useEntityStatusAction } from '@/hooks/useEntityStatusAction';
import { usePermissions } from '@/hooks/usePermissions';
import { type ArReceipt } from '@/types/ar-receipt';

type ArReceiptAction = 'cancel';

export function ArReceiptRowActions(
    props: Readonly<RowActionHandlers<ArReceipt>>,
) {
    const { can } = usePermissions();
    const { status, receipt_number: receiptNumber } = props.item;
    const { run, processing } = useEntityStatusAction<
        ArReceipt,
        ArReceiptAction
    >({
        endpoint: '/api/ar-receipts',
        queryKey: 'ar-receipts',
        successMessages: { cancel: 'Receipt cancelled.' },
        failedMessage: 'Failed to cancel receipt.',
        request: (_action, id) => ({
            method: 'put',
            url: `/api/ar-receipts/${id}`,
            body: { status: 'cancelled' },
        }),
    });

    const actions: StatusActionConfig<ArReceiptAction>[] = [];
    if (can('ar_receipt.edit') && status === 'confirmed') {
        actions.push({
            action: 'cancel',
            label: 'Cancel Receipt',
            title: 'Cancel this receipt?',
            description:
                `Receipt ${receiptNumber ?? ''} will be cancelled, ` +
                'its allocations released and its journal reversed.',
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
