'use client';

import {
    type RowActionHandlers,
    StatusRowActions,
} from '@/components/common/StatusRowActions';
import { usePeriodClosingAction } from '@/hooks/usePeriodClosingAction';
import { type PeriodClosing } from '@/types/period-closing';

export function PeriodClosingRowActions(
    props: Readonly<RowActionHandlers<PeriodClosing>>,
) {
    const { run, processing, processingLabel, getActions } =
        usePeriodClosingAction();

    return (
        <StatusRowActions
            {...props}
            actions={getActions(props.item)}
            run={run}
            processing={processing}
            processingLabel={processingLabel}
        />
    );
}
