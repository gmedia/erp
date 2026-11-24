'use client';

import { type StatusActionConfig } from '@/components/common/StatusActionDialog';
import {
    type RowActionHandlers,
    StatusRowActions,
} from '@/components/common/StatusRowActions';
import { useEntityStatusAction } from '@/hooks/useEntityStatusAction';
import { usePermissions } from '@/hooks/usePermissions';
import { type CustomerInvoice } from '@/types/customer-invoice';

type CustomerInvoiceAction = 'cancel';

export function CustomerInvoiceRowActions(
    props: Readonly<RowActionHandlers<CustomerInvoice>>,
) {
    const { can } = usePermissions();
    const { status, invoice_number: invoiceNumber } = props.item;
    const { run, processing } = useEntityStatusAction<
        CustomerInvoice,
        CustomerInvoiceAction
    >({
        endpoint: '/api/customer-invoices',
        queryKey: 'customer-invoices',
        successMessages: { cancel: 'Invoice cancelled.' },
        failedMessage: 'Failed to cancel invoice.',
        request: (_action, id) => ({
            method: 'put',
            url: `/api/customer-invoices/${id}`,
            body: { status: 'cancelled' },
        }),
    });

    const actions: StatusActionConfig<CustomerInvoiceAction>[] = [];
    if (can('customer_invoice.edit') && status === 'sent') {
        actions.push({
            action: 'cancel',
            label: 'Cancel Invoice',
            title: 'Cancel this invoice?',
            description: `Invoice ${invoiceNumber ?? ''} will be cancelled and its journal reversed.`,
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
