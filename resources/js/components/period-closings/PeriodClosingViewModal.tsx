import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Badge } from '@/components/ui/badge';
import { PeriodClosing } from '@/types/period-closing';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';

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
    if (!item) return null;

    return (
        <ViewModalShell
            open={open}
            onClose={onClose}
            title="Period Closing Details"
            description="View complete details of this period closing"
        >
            <div className="space-y-4 py-4">
                <ViewField
                    label="Fiscal Year"
                    value={item.fiscal_year?.name || '-'}
                />
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <ViewField label="Period Month" value={item.period_month} />
                    <ViewField label="Period Year" value={item.period_year} />
                </div>
                <ViewField
                    label="Closing Type"
                    value={getClosingTypeLabel(item.closing_type)}
                />
                <ViewField
                    label="Retained Earnings Account"
                    value={
                        item.retained_earnings_account
                            ? `${item.retained_earnings_account.code} - ${item.retained_earnings_account.name}`
                            : '-'
                    }
                />
                <ViewField
                    label="Net Income"
                    value={formatCurrencyByRegionalSettings(item.net_income, {
                        locale: 'id-ID',
                        currency: 'IDR',
                    })}
                />
                <ViewField
                    label="Status"
                    value={
                        <Badge variant={getStatusVariant(item.status)}>
                            {item.status === 'draft' ? 'Draft' : 'Closed'}
                        </Badge>
                    }
                />
                {item.journal_entry && (
                    <ViewField
                        label="Journal Entry"
                        value={item.journal_entry.entry_number}
                    />
                )}
                {item.closed_at && (
                    <>
                        <ViewField
                            label="Closed At"
                            value={formatDateByRegionalSettings(item.closed_at)}
                        />
                        <ViewField
                            label="Closed By"
                            value={item.closed_by?.name || '-'}
                        />
                    </>
                )}
                <ViewField label="Created By" value={item.created_by.name} />
                <ViewField
                    label="Created At"
                    value={formatDateByRegionalSettings(item.created_at)}
                />
            </div>
        </ViewModalShell>
    );
}
