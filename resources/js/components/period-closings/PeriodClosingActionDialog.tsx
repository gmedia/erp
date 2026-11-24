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

/** Label periode untuk pesan konfirmasi, mengikuti bahasa aktif. */
function formatPeriodLabel(item: PeriodClosing, locale: string): string {
    if (item.closing_type !== 'monthly' || !item.period_month) {
        return String(item.period_year);
    }

    return new Intl.DateTimeFormat(locale, {
        month: 'long',
        year: 'numeric',
    }).format(new Date(item.period_year, item.period_month - 1, 1));
}

export function PeriodClosingActionDialog({
    action,
    item,
    processing,
    onConfirm,
    onOpenChange,
}: Readonly<PeriodClosingActionDialogProps>) {
    const { t, locale } = useTranslation();
    const period = item ? formatPeriodLabel(item, locale) : '';
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
