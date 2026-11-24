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
import { type PeriodClosingAction } from '@/hooks/usePeriodClosingAction';
import { type PeriodClosing } from '@/types/period-closing';

interface PeriodClosingActionDialogProps {
    action: PeriodClosingAction | null;
    item: PeriodClosing | null;
    processing: boolean;
    onConfirm: () => void;
    onOpenChange: (open: boolean) => void;
}

const MONTH_NAMES = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

/** Label periode untuk pesan konfirmasi, mis. "March 2026" atau "Year 2026". */
export function formatPeriodLabel(item: PeriodClosing): string {
    if (item.closing_type === 'yearly') return `Year ${item.period_year}`;
    const name = MONTH_NAMES[item.period_month - 1] ?? item.period_month;
    return `${name} ${item.period_year}`;
}

export function PeriodClosingActionDialog({
    action,
    item,
    processing,
    onConfirm,
    onOpenChange,
}: Readonly<PeriodClosingActionDialogProps>) {
    const { t } = useTranslation();
    const period = item ? formatPeriodLabel(item) : '';
    const isClose = action === 'close';

    return (
        <AlertDialog open={action !== null} onOpenChange={onOpenChange}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {isClose
                            ? t('periodClosings.closeTitle')
                            : t('periodClosings.reopenTitle')}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {isClose
                            ? t('periodClosings.closeDescription', { period })
                            : t('periodClosings.reopenDescription', {
                                  period,
                              })}
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
                            ? t('periodClosings.processing')
                            : isClose
                              ? t('periodClosings.confirmClose')
                              : t('periodClosings.confirmReopen')}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
