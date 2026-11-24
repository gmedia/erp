'use client';

import { type StatusActionConfig } from '@/components/common/StatusActionDialog';
import {
    type RowActionHandlers,
    StatusRowActions,
} from '@/components/common/StatusRowActions';
import { useEntityStatusAction } from '@/hooks/useEntityStatusAction';
import { usePermissions } from '@/hooks/usePermissions';
import { type CreditNote } from '@/types/credit-note';

type CreditNoteAction = 'cancel';

export function CreditNoteRowActions(
    props: Readonly<RowActionHandlers<CreditNote>>,
) {
    const { can } = usePermissions();
    const { status, credit_note_number: creditNoteNumber } = props.item;
    const { run, processing } = useEntityStatusAction<
        CreditNote,
        CreditNoteAction
    >({
        endpoint: '/api/credit-notes',
        queryKey: 'credit-notes',
        successMessages: { cancel: 'Credit note cancelled.' },
        failedMessage: 'Failed to cancel credit note.',
        request: (_action, id) => ({
            method: 'put',
            url: `/api/credit-notes/${id}`,
            body: { status: 'cancelled' },
        }),
    });

    const actions: StatusActionConfig<CreditNoteAction>[] = [];
    if (
        can('credit_note.edit') &&
        (status === 'confirmed' || status === 'applied')
    ) {
        actions.push({
            action: 'cancel',
            label: 'Cancel Credit Note',
            title: 'Cancel this credit note?',
            description: `Credit note ${creditNoteNumber ?? ''} will be cancelled and its journal reversed.`,
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
