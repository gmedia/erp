'use client';

import { GenericActions } from '@/components/common/ActionsDropdown';
import { PeriodClosingActionDialog } from '@/components/period-closings/PeriodClosingActionDialog';
import { useTranslation } from '@/contexts/i18n-context';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import {
    type PeriodClosingAction,
    usePeriodClosingAction,
} from '@/hooks/usePeriodClosingAction';
import { usePermissions } from '@/hooks/usePermissions';
import { type PeriodClosing } from '@/types/period-closing';
import { useState } from 'react';

interface PeriodClosingRowActionsProps {
    item: PeriodClosing;
    onView?: (item: PeriodClosing) => void;
    onEdit?: (item: PeriodClosing) => void;
    onDelete?: (item: PeriodClosing) => void;
}

export function PeriodClosingRowActions({
    item,
    onView,
    onEdit,
    onDelete,
}: Readonly<PeriodClosingRowActionsProps>) {
    const { t } = useTranslation();
    const { can } = usePermissions();
    const canEdit = can('period_closing.edit');
    const [pendingAction, setPendingAction] =
        useState<PeriodClosingAction | null>(null);
    const { run, processing } = usePeriodClosingAction();

    const showClose = canEdit && item.status !== 'closed';
    const showReopen = canEdit && item.status === 'closed';

    const extraItems = [
        showClose && (
            <DropdownMenuItem
                key="close"
                onSelect={() => setPendingAction('close')}
            >
                {t('periodClosings.close')}
            </DropdownMenuItem>
        ),
        showReopen && (
            <DropdownMenuItem
                key="reopen"
                onSelect={() => setPendingAction('reopen')}
            >
                {t('periodClosings.reopen')}
            </DropdownMenuItem>
        ),
    ].filter(Boolean);

    const handleConfirm = async () => {
        if (!pendingAction) return;
        const ok = await run(item, pendingAction);
        if (ok) setPendingAction(null);
    };

    return (
        <>
            <GenericActions
                item={item}
                onView={onView}
                onEdit={onEdit}
                onDelete={onDelete}
                extraItems={extraItems}
            />
            <PeriodClosingActionDialog
                action={pendingAction}
                item={item}
                processing={processing !== null}
                onConfirm={() => void handleConfirm()}
                onOpenChange={(next) => {
                    if (!next && processing === null) setPendingAction(null);
                }}
            />
        </>
    );
}
