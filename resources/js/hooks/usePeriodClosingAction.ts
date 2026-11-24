'use client';

import { type StatusActionConfig } from '@/components/common/StatusActionDialog';
import { useTranslation } from '@/contexts/i18n-context';
import { useEntityStatusAction } from '@/hooks/useEntityStatusAction';
import { usePermissions } from '@/hooks/usePermissions';
import { type PeriodClosing } from '@/types/period-closing';

export type PeriodClosingAction = 'close' | 'reopen';

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

export function usePeriodClosingAction(
    onSuccess?: (item: PeriodClosing) => void,
) {
    const { t, locale } = useTranslation();
    const { can } = usePermissions();
    const { run, processing } = useEntityStatusAction<
        PeriodClosing,
        PeriodClosingAction
    >({
        endpoint: '/api/period-closings',
        queryKey: 'period-closings',
        successMessages: {
            close: t('periodClosings.closed'),
            reopen: t('periodClosings.reopened'),
        },
        failedMessage: t('periodClosings.failed'),
        onSuccess,
    });

    const getActions = (
        item: PeriodClosing,
    ): StatusActionConfig<PeriodClosingAction>[] => {
        if (!can('period_closing.edit')) return [];

        const period = formatPeriodLabel(item, locale);

        return item.status === 'closed'
            ? [
                  {
                      action: 'reopen',
                      label: t('periodClosings.reopen'),
                      title: t('periodClosings.reopenTitle'),
                      description: t('periodClosings.reopenDescription', {
                          period,
                      }),
                      confirmLabel: t('periodClosings.confirmReopen'),
                  },
              ]
            : [
                  {
                      action: 'close',
                      label: t('periodClosings.close'),
                      title: t('periodClosings.closeTitle'),
                      description: t('periodClosings.closeDescription', {
                          period,
                      }),
                      confirmLabel: t('periodClosings.confirmClose'),
                  },
              ];
    };

    return {
        run,
        processing,
        getActions,
        processingLabel: t('periodClosings.processing'),
    };
}
