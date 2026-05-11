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

function getStatusVariant(status: BankReconciliation['status']) {
    return status === 'completed' ? 'default' : 'secondary';
}

export function BankReconciliationViewModal({
    item,
    open,
    onClose,
}: Readonly<BankReconciliationViewModalProps>) {
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
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-gray-500">
                                Account
                            </p>
                            <p>
                                {item.account_code} - {item.account_name}
                            </p>
                        </div>
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-gray-500">
                                Fiscal Year
                            </p>
                            <p>{item.fiscal_year?.name || '-'}</p>
                        </div>
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-gray-500">
                                Period Start
                            </p>
                            <p>
                                {formatDateByRegionalSettings(
                                    item.period_start,
                                )}
                            </p>
                        </div>
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-gray-500">
                                Period End
                            </p>
                            <p>
                                {formatDateByRegionalSettings(item.period_end)}
                            </p>
                        </div>
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-gray-500">
                                Statement Balance
                            </p>
                            <p>
                                {formatCurrencyByRegionalSettings(
                                    item.statement_balance,
                                    {
                                        locale: 'id-ID',
                                        currency: 'IDR',
                                    },
                                )}
                            </p>
                        </div>
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-gray-500">
                                Book Balance
                            </p>
                            <p>
                                {formatCurrencyByRegionalSettings(
                                    item.book_balance,
                                    {
                                        locale: 'id-ID',
                                        currency: 'IDR',
                                    },
                                )}
                            </p>
                        </div>
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-gray-500">
                                Difference
                            </p>
                            <p>
                                {formatCurrencyByRegionalSettings(
                                    item.difference,
                                    {
                                        locale: 'id-ID',
                                        currency: 'IDR',
                                    },
                                )}
                            </p>
                        </div>
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-gray-500">
                                Status
                            </p>
                            <Badge variant={getStatusVariant(item.status)}>
                                {item.status === 'in_progress'
                                    ? 'In Progress'
                                    : 'Completed'}
                            </Badge>
                        </div>
                        {item.completed_at && (
                            <>
                                <div className="min-w-0">
                                    <p className="text-sm font-medium text-gray-500">
                                        Completed At
                                    </p>
                                    <p>
                                        {formatDateByRegionalSettings(
                                            item.completed_at,
                                        )}
                                    </p>
                                </div>
                                <div className="min-w-0">
                                    <p className="text-sm font-medium text-gray-500">
                                        Completed By
                                    </p>
                                    <p>{item.completed_by?.name || '-'}</p>
                                </div>
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
                                        <TableHead>Journal Entry</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead className="text-right">
                                            Debit
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Credit
                                        </TableHead>
                                        <TableHead>Reconciled</TableHead>
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
                                                    {itemRow.journal_entry_number ||
                                                        '-'}
                                                </TableCell>
                                                <TableCell>
                                                    {itemRow.description}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrencyByRegionalSettings(
                                                        itemRow.debit,
                                                        {
                                                            locale: 'id-ID',
                                                            currency: 'IDR',
                                                        },
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrencyByRegionalSettings(
                                                        itemRow.credit,
                                                        {
                                                            locale: 'id-ID',
                                                            currency: 'IDR',
                                                        },
                                                    )}
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
}
