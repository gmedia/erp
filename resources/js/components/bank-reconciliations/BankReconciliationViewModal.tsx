import { memo } from 'react';

import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { BankReconciliation } from '@/types/bank-reconciliation';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';

interface BankReconciliationViewModalProps {
    item: BankReconciliation | null;
    open: boolean;
    onClose: () => void;
}

const currencyOpts = { locale: 'id-ID', currency: 'IDR' } as const;

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

                            <div className="space-y-2">
                                <h3 className="text-lg font-semibold">
                                    Reconciliation Items
                                </h3>
                                <div className="min-w-0 rounded-md border">
                                    <Table className="min-w-[600px]">
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Date</TableHead>
                                                <TableHead>
                                                    Description
                                                </TableHead>
                                                <TableHead className="text-right">
                                                    Debit
                                                </TableHead>
                                                <TableHead className="text-right">
                                                    Credit
                                                </TableHead>
                                                <TableHead>Type</TableHead>
                                                <TableHead>
                                                    Reconciled
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {item.items.length === 0 ? (
                                                <TableRow>
                                                    <TableCell
                                                        colSpan={6}
                                                        className="text-center text-muted-foreground"
                                                    >
                                                        No items found.
                                                    </TableCell>
                                                </TableRow>
                                            ) : (
                                                item.items.map((itemRow) => (
                                                    <TableRow key={itemRow.id}>
                                                        <TableCell>
                                                            {formatDateByRegionalSettings(
                                                                itemRow.transaction_date,
                                                            )}
                                                        </TableCell>
                                                        <TableCell>
                                                            {
                                                                itemRow.description
                                                            }
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            {formatCurrencyByRegionalSettings(
                                                                itemRow.debit,
                                                                currencyOpts,
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            {formatCurrencyByRegionalSettings(
                                                                itemRow.credit,
                                                                currencyOpts,
                                                            )}
                                                        </TableCell>
                                                        <TableCell>
                                                            {itemRow.type}
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge
                                                                variant={
                                                                    itemRow.is_reconciled
                                                                        ? 'default'
                                                                        : 'secondary'
                                                                }
                                                            >
                                                                {itemRow.is_reconciled
                                                                    ? 'Yes'
                                                                    : 'No'}
                                                            </Badge>
                                                        </TableCell>
                                                    </TableRow>
                                                ))
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>
                        </div>
                    </div>
                </ViewModalShell>
            );
        },
    );
