'use client';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { useTranslation } from '@/contexts/i18n-context';
import { type BudgetAction } from '@/hooks/useBudgetAction';
import { type Budget } from '@/types/budget';

interface BudgetActionDialogProps {
    action: BudgetAction | null;
    item: Budget | null;
    processing: boolean;
    onConfirm: () => void;
    onOpenChange: (open: boolean) => void;
}

export function BudgetActionDialog({
    action,
    item,
    processing,
    onConfirm,
    onOpenChange,
}: Readonly<BudgetActionDialogProps>) {
    const { t } = useTranslation();
    const name = item?.name ?? '';
    const isApprove = action === 'approve';

    return (
        <AlertDialog open={action !== null} onOpenChange={onOpenChange}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {isApprove
                            ? t('budgets.approveTitle')
                            : t('budgets.lockTitle')}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {isApprove
                            ? t('budgets.approveDescription', { name })
                            : t('budgets.lockDescription', { name })}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={processing}>
                        {t('common.cancel')}
                    </AlertDialogCancel>
                    <AlertDialogAction
                        onClick={(event) => {
                            event.preventDefault();
                            onConfirm();
                        }}
                        disabled={processing}
                    >
                        {processing
                            ? t('budgets.processing')
                            : isApprove
                              ? t('budgets.confirmApprove')
                              : t('budgets.confirmLock')}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
