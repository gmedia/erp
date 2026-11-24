'use client';

import { type StatusActionConfig } from '@/components/common/StatusActionDialog';
import {
    type RowActionHandlers,
    StatusRowActions,
} from '@/components/common/StatusRowActions';
import { useTranslation } from '@/contexts/i18n-context';
import { useEntityStatusAction } from '@/hooks/useEntityStatusAction';
import { usePermissions } from '@/hooks/usePermissions';
import { type Budget } from '@/types/budget';

type BudgetAction = 'approve' | 'lock';

export function BudgetRowActions(props: Readonly<RowActionHandlers<Budget>>) {
    const { t } = useTranslation();
    const { can } = usePermissions();
    const { status, name } = props.item;
    const { run, processing } = useEntityStatusAction<Budget, BudgetAction>({
        endpoint: '/api/budgets',
        queryKey: 'budgets',
        successMessages: {
            approve: t('budgets.approved'),
            lock: t('budgets.locked'),
        },
        failedMessage: t('budgets.actionFailed'),
    });

    const actions: StatusActionConfig<BudgetAction>[] = [];
    if (can('budget.edit') && status === 'draft') {
        actions.push({
            action: 'approve',
            label: t('budgets.approve'),
            title: t('budgets.approveTitle'),
            description: t('budgets.approveDescription', { name }),
            confirmLabel: t('budgets.confirmApprove'),
        });
    }
    if (can('budget.edit') && status === 'approved') {
        actions.push({
            action: 'lock',
            label: t('budgets.lock'),
            title: t('budgets.lockTitle'),
            description: t('budgets.lockDescription', { name }),
            confirmLabel: t('budgets.confirmLock'),
        });
    }

    return (
        <StatusRowActions
            {...props}
            actions={actions}
            run={run}
            processing={processing}
            processingLabel={t('budgets.processing')}
        />
    );
}
