import { memo } from 'react';

import { ViewField } from '@/components/common/ViewField';
import {
    ViewModalItemsTable,
    type ViewModalItemsTableColumn,
} from '@/components/common/ViewModalItemsTable';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Badge } from '@/components/ui/badge';
import {
    BankReconciliation,
    type BankReconciliationItem,
} from '@/types/bank-reconciliation';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';

interface BankReconciliationViewModalProps {
    item: BankReconciliation | null;
    open: boolean;
    onClose: () => void;
}

const currencyOpts = { locale: 'id-ID', currency: 'IDR' } as const;

const itemColumns: ViewModalItemsTableColumn<BankReconciliationItem>[] = [
    {
        key: 'transaction_date',
        header: 'Date',
        render: (row) => formatDateByRegionalSettings(row.transaction_date),
    },
    {
        key: 'description',
        header: 'Description',
        render: (row) => row.description,
    },
    {
        key: 'debit',
        header: 'Debit',
        align: 'right',
        render: (row) =>
            formatCurrencyByRegionalSettings(row.debit, currencyOpts),
    },
    {
        key: 'credit',
        header: 'Credit',
        align: 'right',
        render: (row) =>
            formatCurrencyByRegionalSettings(row.credit, currencyOpts),
    },
    { key: 'type', header: 'Type', render: (row) => row.type },
    {
        key: 'reconciled',
        header: 'Reconciled',
        render: (row) => (
            <Badge variant={row.is_reconciled ? 'default' : 'secondary'}>
                {row.is_reconciled ? 'Yes' : 'No'}
            </Badge>
        ),
    },
];

export const BankReconciliationViewModal =
    memo<BankReconciliationViewModalProps>(
        function BankReconciliationViewModal({ item, open, onClose }) {
            if (!item) return null;

            return (
                <ViewModalShell
                    open={open}
                    onClose={onClose}
                    title="Bank Reconciliation Details"
                    description="View complete details of this bank reconciliation"
                    contentClassName="flex max-h-[90vh] max-w-[95vw] flex-col overflow-hidden sm:max-w-7xl"
                >
                    <div className="min-h-0 flex-1 overflow-y-auto sm:pr-4">
                        <div className="space-y-6 py-2">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <ViewField
                                    label="Account"
                                    value={`${item.account_code} - ${item.account_name}`}
                                />
                                <ViewField
                                    label="Fiscal Year"
                                    value={item.fiscal_year?.name || '-'}
                                />
                                <ViewField
                                    label="Period Start"
                                    value={formatDateByRegionalSettings(
                                        item.period_start,
                                    )}
                                />
                                <ViewField
                                    label="Period End"
                                    value={formatDateByRegionalSettings(
                                        item.period_end,
                                    )}
                                />
                                <ViewField
                                    label="Statement Balance"
                                    value={formatCurrencyByRegionalSettings(
                                        item.statement_balance,
                                        currencyOpts,
                                    )}
                                />
                                <ViewField
                                    label="Book Balance"
                                    value={formatCurrencyByRegionalSettings(
                                        item.book_balance,
                                        currencyOpts,
                                    )}
                                />
                                <ViewField
                                    label="Difference"
                                    value={formatCurrencyByRegionalSettings(
                                        item.difference,
                                        currencyOpts,
                                    )}
                                />
                                <ViewField
                                    label="Status"
                                    value={
                                        <Badge
                                            variant={
                                                item.status === 'completed'
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {item.status === 'in_progress'
                                                ? 'In Progress'
                                                : 'Completed'}
                                        </Badge>
                                    }
                                />
                                {item.completed_at && (
                                    <>
                                        <ViewField
                                            label="Completed At"
                                            value={formatDateByRegionalSettings(
                                                item.completed_at,
                                            )}
                                        />
                                        <ViewField
                                            label="Completed By"
                                            value={
                                                item.completed_by?.name || '-'
                                            }
                                        />
                                    </>
                                )}
                            </div>

                            <ViewModalItemsTable
                                items={item.items}
                                columns={itemColumns}
                                minWidthClassName="min-w-[600px]"
                                title="Reconciliation Items"
                                getRowKey={(row) => row.id ?? 0}
                            />
                        </div>
                    </div>
                </ViewModalShell>
            );
        },
    );
