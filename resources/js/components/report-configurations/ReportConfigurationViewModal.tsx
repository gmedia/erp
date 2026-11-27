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
import { formatClassName } from '@/lib/status-badge';
import { type ReportConfiguration } from '@/types/report-configuration';

interface ReportConfigurationViewModalProps {
    item: ReportConfiguration | null;
    open: boolean;
    onClose: () => void;
}

const reportTypeLabels: Record<string, string> = {
    balance_sheet: 'Balance Sheet',
    income_statement: 'Income Statement',
    cash_flow: 'Cash Flow',
    trial_balance: 'Trial Balance',
    custom: 'Custom',
};

const sectionTypeLabels: Record<string, string> = {
    header: 'Header',
    detail: 'Detail',
    subtotal: 'Subtotal',
    total: 'Total',
    separator: 'Separator',
};

export const ReportConfigurationViewModal =
    memo<ReportConfigurationViewModalProps>(
        function ReportConfigurationViewModal({ item, open, onClose }) {
            if (!item) return null;

            return (
                <ViewModalShell
                    open={open}
                    onClose={onClose}
                    title="Report Configuration Details"
                    description="View full configuration including sections"
                    contentClassName="flex max-h-[90vh] max-w-[95vw] flex-col overflow-hidden sm:max-w-5xl"
                >
                    <div className="min-h-0 flex-1 overflow-y-auto sm:pr-4">
                        <div className="space-y-6 py-2">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <ViewField label="Code" value={item.code} />
                                <ViewField label="Name" value={item.name} />
                                <ViewField
                                    label="Report Type"
                                    value={
                                        reportTypeLabels[item.report_type] ??
                                        formatClassName(item.report_type)
                                    }
                                />
                                <ViewField
                                    label="Status"
                                    value={
                                        <Badge
                                            variant={
                                                item.is_active
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {item.is_active
                                                ? 'Active'
                                                : 'Inactive'}
                                        </Badge>
                                    }
                                />
                                <div className="sm:col-span-2">
                                    <ViewField
                                        label="Description"
                                        value={item.description || '-'}
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <h4 className="text-sm font-semibold">
                                    Sections ({item.sections?.length ?? 0})
                                </h4>
                                <div className="rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Order</TableHead>
                                                <TableHead>Code</TableHead>
                                                <TableHead>Name</TableHead>
                                                <TableHead className="hidden sm:table-cell">
                                                    Type
                                                </TableHead>
                                                <TableHead className="hidden sm:table-cell">
                                                    Account Type
                                                </TableHead>
                                                <TableHead className="hidden sm:table-cell">
                                                    Sub Type
                                                </TableHead>
                                                <TableHead className="hidden sm:table-cell">
                                                    Sign
                                                </TableHead>
                                                <TableHead>Formula</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {(item.sections ?? []).length ===
                                            0 ? (
                                                <TableRow>
                                                    <TableCell
                                                        colSpan={8}
                                                        className="text-center text-muted-foreground"
                                                    >
                                                        No sections defined.
                                                    </TableCell>
                                                </TableRow>
                                            ) : (
                                                (item.sections ?? []).map(
                                                    (section) => (
                                                        <TableRow
                                                            key={
                                                                section.id ??
                                                                section.code
                                                            }
                                                        >
                                                            <TableCell>
                                                                {
                                                                    section.sort_order
                                                                }
                                                            </TableCell>
                                                            <TableCell className="max-w-24 font-mono text-xs">
                                                                <span
                                                                    className="block truncate"
                                                                    title={
                                                                        section.code
                                                                    }
                                                                >
                                                                    {
                                                                        section.code
                                                                    }
                                                                </span>
                                                            </TableCell>
                                                            <TableCell>
                                                                {section.name}
                                                            </TableCell>
                                                            <TableCell className="hidden sm:table-cell">
                                                                <Badge variant="outline">
                                                                    {sectionTypeLabels[
                                                                        section
                                                                            .section_type
                                                                    ] ??
                                                                        formatClassName(
                                                                            section.section_type,
                                                                        )}
                                                                </Badge>
                                                            </TableCell>
                                                            <TableCell className="hidden sm:table-cell">
                                                                {section.account_type_filter ||
                                                                    '-'}
                                                            </TableCell>
                                                            <TableCell className="hidden sm:table-cell">
                                                                {section.account_sub_type_filter ||
                                                                    '-'}
                                                            </TableCell>
                                                            <TableCell className="hidden sm:table-cell">
                                                                {
                                                                    section.sign_convention
                                                                }
                                                            </TableCell>
                                                            <TableCell className="max-w-48 font-mono text-xs">
                                                                <span
                                                                    className="block truncate"
                                                                    title={
                                                                        section.formula ||
                                                                        undefined
                                                                    }
                                                                >
                                                                    {section.formula ||
                                                                        '-'}
                                                                </span>
                                                            </TableCell>
                                                        </TableRow>
                                                    ),
                                                )
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
