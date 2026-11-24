import { StatusActionDialog } from '@/components/common/StatusActionDialog';
import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    type PeriodClosingAction,
    usePeriodClosingAction,
} from '@/hooks/usePeriodClosingAction';
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

export function PeriodClosingViewModal({
    item,
    open,
    onClose,
}: Readonly<PeriodClosingViewModalProps>) {
    const [pendingAction, setPendingAction] =
        useState<PeriodClosingAction | null>(null);
    // Hasil aksi hanya berlaku untuk item yang sedang dibuka; data baris baru mengalahkannya.
    const [updated, setUpdated] = useState<{
        source: PeriodClosing | null;
        next: PeriodClosing;
    } | null>(null);
    const { run, processing, processingLabel, getActions } =
        usePeriodClosingAction((next) => setUpdated({ source: item, next }));

    if (!item) return null;

    const view = updated?.source === item ? updated.next : item;
    const actions = getActions(view);

    const handleConfirm = async () => {
        if (!pendingAction) return;
        const ok = await run(view, pendingAction);
        if (ok) setPendingAction(null);
    };

    const footer =
        actions.length > 0 ? (
            <div className="flex w-full flex-wrap items-center justify-end gap-2">
                {actions.map(({ action, label }) => (
                    <Button
                        key={action}
                        type="button"
                        variant={action === 'reopen' ? 'outline' : 'default'}
                        onClick={() => setPendingAction(action)}
                        disabled={processing}
                    >
                        {label}
                    </Button>
                ))}
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
            <StatusActionDialog
                config={actions.find(({ action }) => action === pendingAction)}
                processing={processing}
                processingLabel={processingLabel}
                onConfirm={() => void handleConfirm()}
                onCancel={() => setPendingAction(null)}
            />
            <div className="space-y-4 py-4">
                <ViewField
                    label="Fiscal Year"
                    value={view.fiscal_year?.name || '-'}
                />
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <ViewField
                        label="Period Month"
                        value={view.period_month ?? '-'}
                    />
                    <ViewField label="Period Year" value={view.period_year} />
                </div>
                <ViewField
                    label="Closing Type"
                    value={
                        view.closing_type === 'monthly'
                            ? t('periodClosings.typeMonthly')
                            : t('periodClosings.typeAnnual')
                    }
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
