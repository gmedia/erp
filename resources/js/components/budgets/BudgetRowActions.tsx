'use client';

import { BudgetActionDialog } from '@/components/budgets/BudgetActionDialog';
import { GenericActions } from '@/components/common/ActionsDropdown';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { useTranslation } from '@/contexts/i18n-context';
import { type BudgetAction, useBudgetAction } from '@/hooks/useBudgetAction';
import { usePermissions } from '@/hooks/usePermissions';
import { type Budget } from '@/types/budget';
import { useState } from 'react';

interface BudgetRowActionsProps {
    item: Budget;
    onView?: (item: Budget) => void;
    onEdit?: (item: Budget) => void;
    onDelete?: (item: Budget) => void;
}

export function BudgetRowActions({
    item,
    onView,
    onEdit,
    onDelete,
}: Readonly<BudgetRowActionsProps>) {
    const { t } = useTranslation();
    const { can } = usePermissions();
    const canEdit = can('budget.edit');
    const [pendingAction, setPendingAction] = useState<BudgetAction | null>(
        null,
    );
    const { run, processing } = useBudgetAction();

    const showApprove = canEdit && item.status === 'draft';
    const showLock = canEdit && item.status === 'approved';

    const extraItems = [
        showApprove && (
            <DropdownMenuItem
                key="approve"
                onSelect={() => setPendingAction('approve')}
            >
                {t('budgets.approve')}
            </DropdownMenuItem>
        ),
        showLock && (
            <DropdownMenuItem
                key="lock"
                onSelect={() => setPendingAction('lock')}
            >
                {t('budgets.lock')}
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
            <BudgetActionDialog
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
