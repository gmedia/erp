import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { PeriodClosingActionDialog } from '@/components/period-closings/PeriodClosingActionDialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/contexts/i18n-context';
import {
    type PeriodClosingAction,
    usePeriodClosingAction,
} from '@/hooks/usePeriodClosingAction';
import { usePermissions } from '@/hooks/usePermissions';
import { formatStatusLabel } from '@/lib/status-badge';
import { PeriodClosing } from '@/types/period-closing';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import { useState } from 'react';

interface PeriodClosingViewModalProps {
    item: PeriodClosing | null;
    open: boolean;
    onClose: () => void;
}

function getStatusVariant(status: PeriodClosing['status']) {
    return status === 'closed' ? 'default' : 'secondary';
}

function getClosingTypeLabel(type: PeriodClosing['closing_type']) {
    return type === 'monthly' ? 'Monthly' : 'Yearly';
}

export function PeriodClosingViewModal({
    item,
    open,
    onClose,
}: Readonly<PeriodClosingViewModalProps>) {
    const { t } = useTranslation();
    const { can } = usePermissions();
    const canEdit = can('period_closing.edit');

    const [pendingAction, setPendingAction] =
        useState<PeriodClosingAction | null>(null);
    // Hasil aksi hanya berlaku untuk item yang sedang dibuka; data baris baru mengalahkannya.
    const [updated, setUpdated] = useState<{
        source: PeriodClosing | null;
        next: PeriodClosing;
    } | null>(null);
    const { run, processing } = usePeriodClosingAction({
        onSuccess: (next) => setUpdated({ source: item, next }),
    });

    if (!item) return null;

    const view = updated?.source === item ? updated.next : item;
    const isBusy = processing !== null;
    const canClose = canEdit && view.status !== 'closed';
    const canReopen = canEdit && view.status === 'closed';

    const handleConfirm = async () => {
        if (!pendingAction) return;
        const ok = await run(view, pendingAction);
        if (ok) setPendingAction(null);
    };

    const footer =
        canClose || canReopen ? (
            <div className="flex w-full flex-wrap items-center justify-end gap-2">
                {canClose && (
                    <Button
                        type="button"
                        onClick={() => setPendingAction('close')}
                        disabled={isBusy}
                    >
                        {t('periodClosings.close')}
                    </Button>
                )}
                {canReopen && (
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => setPendingAction('reopen')}
                        disabled={isBusy}
                    >
                        {t('periodClosings.reopen')}
                    </Button>
                )}
            </div>
        ) : undefined;

    return (
        <ViewModalShell
            open={open}
            onClose={onClose}
            title="Period Closing Details"
            description="View complete details of this period closing"
            footer={footer}
        >
            <PeriodClosingActionDialog
                action={pendingAction}
                item={view}
                processing={isBusy}
                onConfirm={() => void handleConfirm()}
                onOpenChange={(next) => {
                    if (!next && !isBusy) setPendingAction(null);
                }}
            />
            <div className="space-y-4 py-4">
                <ViewField
                    label="Fiscal Year"
                    value={view.fiscal_year?.name || '-'}
                />
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <ViewField label="Period Month" value={view.period_month} />
                    <ViewField label="Period Year" value={view.period_year} />
                </div>
                <ViewField
                    label="Closing Type"
                    value={getClosingTypeLabel(view.closing_type)}
                />
                <ViewField
                    label="Retained Earnings Account"
                    value={
                        view.retained_earnings_account
                            ? `${view.retained_earnings_account.code} - ${view.retained_earnings_account.name}`
                            : '-'
                    }
                />
                <ViewField
                    label="Net Income"
                    value={formatCurrencyByRegionalSettings(view.net_income, {
                        locale: 'id-ID',
                        currency: 'IDR',
                    })}
                />
                <ViewField
                    label="Status"
                    value={
                        <Badge variant={getStatusVariant(view.status)}>
                            {formatStatusLabel(view.status)}
                        </Badge>
                    }
                />
                {view.journal_entry && (
                    <ViewField
                        label="Journal Entry"
                        value={view.journal_entry.entry_number}
                    />
                )}
                {view.closed_at && (
                    <>
                        <ViewField
                            label="Closed At"
                            value={formatDateByRegionalSettings(view.closed_at)}
                        />
                        <ViewField
                            label="Closed By"
                            value={view.closed_by?.name || '-'}
                        />
                    </>
                )}
            </div>
        </ViewModalShell>
    );
}
