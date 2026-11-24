'use client';

import { type StatusActionConfig } from '@/components/common/StatusActionDialog';
import {
    type RowActionHandlers,
    StatusRowActions,
} from '@/components/common/StatusRowActions';
import { useEntityStatusAction } from '@/hooks/useEntityStatusAction';
import { usePermissions } from '@/hooks/usePermissions';
import { type SupplierBill } from '@/types/supplier-bill';

type SupplierBillAction = 'cancel';

export function SupplierBillRowActions(
    props: Readonly<RowActionHandlers<SupplierBill>>,
) {
    const { can } = usePermissions();
    const { status, bill_number: billNumber } = props.item;
    const { run, processing } = useEntityStatusAction<
        SupplierBill,
        SupplierBillAction
    >({
        endpoint: '/api/supplier-bills',
        queryKey: 'supplier-bills',
        successMessages: { cancel: 'Bill cancelled.' },
        failedMessage: 'Failed to cancel bill.',
        request: (_action, id) => ({
            method: 'put',
            url: `/api/supplier-bills/${id}`,
            body: { status: 'cancelled' },
        }),
    });

    const actions: StatusActionConfig<SupplierBillAction>[] = [];
    if (can('supplier_bill.edit') && status === 'confirmed') {
        actions.push({
            action: 'cancel',
            label: 'Cancel Bill',
            title: 'Cancel this bill?',
            description: `Bill ${billNumber ?? ''} will be cancelled and its journal reversed.`,
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
