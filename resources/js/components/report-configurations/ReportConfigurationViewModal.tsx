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
import { useTranslation } from '@/contexts/i18n-context';
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
            const { t } = useTranslation();

            if (!item) return null;

            const sections = item.sections ?? [];
            const sectionTypeLabel = (type: string) =>
                sectionTypeLabels[type] ?? formatClassName(type);

            return (
                <ViewModalShell
                    open={open}
                    onClose={onClose}
                    title={t('page.report_configurations.view.title')}
                    description={t(
                        'page.report_configurations.view.description',
                    )}
                    contentClassName="flex max-h-[90vh] max-w-[95vw] flex-col overflow-hidden sm:max-w-5xl"
                >
                    <div className="min-h-0 flex-1 overflow-y-auto sm:pr-4">
                        <div className="space-y-6 py-2">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <ViewField
                                    label={t(
                                        'page.report_configurations.view.code',
                                    )}
                                    value={item.code}
                                />
                                <ViewField
                                    label={t(
                                        'page.report_configurations.view.name',
                                    )}
                                    value={item.name}
                                />
                                <ViewField
                                    label={t(
                                        'page.report_configurations.view.report_type',
                                    )}
                                    value={
                                        reportTypeLabels[item.report_type] ??
                                        formatClassName(item.report_type)
                                    }
                                />
                                <ViewField
                                    label={t(
                                        'page.report_configurations.view.status',
                                    )}
                                    value={
                                        <Badge
                                            variant={
                                                item.is_active
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {item.is_active
                                                ? t(
                                                      'page.report_configurations.view.active',
                                                  )
                                                : t(
                                                      'page.report_configurations.view.inactive',
                                                  )}
                                        </Badge>
                                    }
                                />
                                <div className="sm:col-span-2">
                                    <ViewField
                                        label={t(
                                            'page.report_configurations.view.description_label',
                                        )}
                                        value={item.description || '-'}
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <h4 className="text-sm font-semibold">
                                    {t(
                                        'page.report_configurations.view.sections_count',
                                        {
                                            count: String(sections.length),
                                        },
                                    )}
                                </h4>
                                <div className="rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>
                                                    {t(
                                                        'page.report_configurations.view.order',
                                                    )}
                                                </TableHead>
                                                <TableHead>
                                                    {t(
                                                        'page.report_configurations.view.code',
                                                    )}
                                                </TableHead>
                                                <TableHead>
                                                    {t(
                                                        'page.report_configurations.view.name',
                                                    )}
                                                </TableHead>
                                                <TableHead className="hidden sm:table-cell">
                                                    {t(
                                                        'page.report_configurations.view.type',
                                                    )}
                                                </TableHead>
                                                <TableHead className="hidden sm:table-cell">
                                                    {t(
                                                        'page.report_configurations.view.account_type',
                                                    )}
                                                </TableHead>
                                                <TableHead className="hidden sm:table-cell">
                                                    {t(
                                                        'page.report_configurations.view.sub_type',
                                                    )}
                                                </TableHead>
                                                <TableHead className="hidden sm:table-cell">
                                                    {t(
                                                        'page.report_configurations.view.sign',
                                                    )}
                                                </TableHead>
                                                <TableHead>
                                                    {t(
                                                        'page.report_configurations.view.formula',
                                                    )}
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {sections.length === 0 ? (
                                                <TableRow>
                                                    <TableCell
                                                        colSpan={8}
                                                        className="text-center text-muted-foreground"
                                                    >
                                                        {t(
                                                            'page.report_configurations.view.no_sections',
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            ) : (
                                                sections.map((section) => (
                                                    <TableRow
                                                        key={
                                                            section.id ??
                                                            section.code
                                                        }
                                                    >
                                                        <TableCell>
                                                            {section.sort_order}
                                                        </TableCell>
                                                        <TableCell className="max-w-24 font-mono text-xs">
                                                            <span
                                                                className="block truncate"
                                                                title={
                                                                    section.code
                                                                }
                                                            >
                                                                {section.code}
                                                            </span>
                                                        </TableCell>
                                                        <TableCell>
                                                            <span
                                                                className="block max-w-48 truncate sm:max-w-none"
                                                                title={
                                                                    section.name
                                                                }
                                                            >
                                                                {section.name}
                                                            </span>
                                                            <span className="mt-1 flex flex-wrap gap-1 text-xs text-muted-foreground sm:hidden">
                                                                <span>
                                                                    {sectionTypeLabel(
                                                                        section.section_type,
                                                                    )}
                                                                </span>
                                                                <span>·</span>
                                                                <span>
                                                                    {section.account_type_filter ||
                                                                        '-'}
                                                                </span>
                                                                <span>·</span>
                                                                <span>
                                                                    {section.account_sub_type_filter ||
                                                                        '-'}
                                                                </span>
                                                                <span>·</span>
                                                                <span>
                                                                    {
                                                                        section.sign_convention
                                                                    }
                                                                </span>
                                                            </span>
                                                        </TableCell>
                                                        <TableCell className="hidden sm:table-cell">
                                                            <Badge variant="outline">
                                                                {sectionTypeLabel(
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
