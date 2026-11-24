'use client';

import { type StatusActionConfig } from '@/components/common/StatusActionDialog';
import {
    type RowActionHandlers,
    StatusRowActions,
} from '@/components/common/StatusRowActions';
import { useEntityStatusAction } from '@/hooks/useEntityStatusAction';
import { usePermissions } from '@/hooks/usePermissions';
import { type ApPayment } from '@/types/ap-payment';

type ApPaymentAction = 'cancel';

export function ApPaymentRowActions(
    props: Readonly<RowActionHandlers<ApPayment>>,
) {
    const { can } = usePermissions();
    const { status, payment_number: paymentNumber } = props.item;
    const { run, processing } = useEntityStatusAction<
        ApPayment,
        ApPaymentAction
    >({
        endpoint: '/api/ap-payments',
        queryKey: 'ap-payments',
        successMessages: { cancel: 'Payment cancelled.' },
        failedMessage: 'Failed to cancel payment.',
        request: (_action, id) => ({
            method: 'put',
            url: `/api/ap-payments/${id}`,
            body: { status: 'cancelled' },
        }),
    });

    const actions: StatusActionConfig<ApPaymentAction>[] = [];
    if (can('ap_payment.edit') && status === 'confirmed') {
        actions.push({
            action: 'cancel',
            label: 'Cancel Payment',
            title: 'Cancel this payment?',
            description:
                `Payment ${paymentNumber ?? ''} will be cancelled, ` +
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
