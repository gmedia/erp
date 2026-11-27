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
import { cn } from '@/lib/utils';
import { type ReportConfiguration } from '@/types/report-configuration';

interface ReportConfigurationViewModalProps {
    item: ReportConfiguration | null;
    open: boolean;
    onClose: () => void;
}

const REPORT_TYPE_KEYS = [
    'balance_sheet',
    'income_statement',
    'cash_flow',
    'trial_balance',
    'custom',
] as const;

const SECTION_TYPE_KEYS = [
    'header',
    'detail',
    'subtotal',
    'total',
    'separator',
] as const;

const SIGN_KEYS = ['normal', 'reversed'] as const;

const ACCOUNT_TYPE_KEYS = [
    'asset',
    'liability',
    'equity',
    'revenue',
    'expense',
] as const;

const SUB_TYPE_KEYS = [
    'current_asset',
    'fixed_asset',
    'other_asset',
    'current_liability',
    'long_term_liability',
    'paid_in_capital',
    'retained_earnings',
    'operating_revenue',
    'non_operating_revenue',
    'cost_of_goods_sold',
    'operating_expense',
    'depreciation_expense',
    'non_operating_expense',
    'tax_expense',
] as const;

const isKnown = <T extends string>(
    keys: readonly T[],
    value: string,
): value is T => (keys as readonly string[]).includes(value);

export const ReportConfigurationViewModal =
    memo<ReportConfigurationViewModalProps>(
        function ReportConfigurationViewModal({ item, open, onClose }) {
            const { t } = useTranslation();

            if (!item) return null;

            const sections = item.sections ?? [];
            const reportTypeLabel = isKnown(REPORT_TYPE_KEYS, item.report_type)
                ? t(
                      `page.report_configurations.view_report_type_${item.report_type}`,
                  )
                : formatClassName(item.report_type);
            const sectionTypeLabel = (type: string) =>
                isKnown(SECTION_TYPE_KEYS, type)
                    ? t(`page.report_configurations.view_section_type_${type}`)
                    : formatClassName(type);
            const signLabel = (sign: string) =>
                isKnown(SIGN_KEYS, sign)
                    ? t(`page.report_configurations.view_sign_${sign}`)
                    : sign;
            const accountTypeLabel = (value?: string | null) =>
                value
                    ? isKnown(ACCOUNT_TYPE_KEYS, value)
                        ? t(
                              `page.report_configurations.view_account_type_${value}`,
                          )
                        : formatClassName(value)
                    : '-';
            const subTypeLabel = (value?: string | null) =>
                value
                    ? isKnown(SUB_TYPE_KEYS, value)
                        ? t(`page.report_configurations.view_sub_type_${value}`)
                        : formatClassName(value)
                    : '-';

            return (
                <ViewModalShell
                    open={open}
                    onClose={onClose}
                    title={t('page.report_configurations.view_title')}
                    description={t(
                        'page.report_configurations.view_description',
                    )}
                    contentClassName="flex max-h-[90vh] max-w-[95vw] flex-col overflow-hidden sm:max-w-5xl"
                >
                    <div className="min-h-0 flex-1 overflow-y-auto sm:pr-4">
                        <div className="space-y-6 py-2">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <ViewField
                                    label={t(
                                        'page.report_configurations.view_code',
                                    )}
                                    value={item.code}
                                />
                                <ViewField
                                    label={t(
                                        'page.report_configurations.view_name',
                                    )}
                                    value={item.name}
                                />
                                <ViewField
                                    label={t(
                                        'page.report_configurations.view_report_type',
                                    )}
                                    value={reportTypeLabel}
                                />
                                <ViewField
                                    label={t(
                                        'page.report_configurations.view_status',
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
                                                      'page.report_configurations.view_active',
                                                  )
                                                : t(
                                                      'page.report_configurations.view_inactive',
                                                  )}
                                        </Badge>
                                    }
                                />
                                <div className="sm:col-span-2">
                                    <ViewField
                                        label={t(
                                            'page.report_configurations.view_description_label',
                                        )}
                                        value={item.description || '-'}
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <h4 className="text-sm font-semibold">
                                    {t(
                                        'page.report_configurations.view_sections_count',
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
                                                        'page.report_configurations.view_order',
                                                    )}
                                                </TableHead>
                                                <TableHead>
                                                    {t(
                                                        'page.report_configurations.view_code',
                                                    )}
                                                </TableHead>
                                                <TableHead>
                                                    {t(
                                                        'page.report_configurations.view_name',
                                                    )}
                                                </TableHead>
                                                <TableHead className="hidden sm:table-cell">
                                                    {t(
                                                        'page.report_configurations.view_type',
                                                    )}
                                                </TableHead>
                                                <TableHead className="hidden sm:table-cell">
                                                    {t(
                                                        'page.report_configurations.view_account_type',
                                                    )}
                                                </TableHead>
                                                <TableHead className="hidden sm:table-cell">
                                                    {t(
                                                        'page.report_configurations.view_sub_type',
                                                    )}
                                                </TableHead>
                                                <TableHead className="hidden sm:table-cell">
                                                    {t(
                                                        'page.report_configurations.view_sign',
                                                    )}
                                                </TableHead>
                                                <TableHead>
                                                    {t(
                                                        'page.report_configurations.view_formula',
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
                                                            'page.report_configurations.view_no_sections',
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
                                                        <TableCell className="min-w-0 break-words whitespace-normal">
                                                            <span className="block font-medium">
                                                                {section.name}
                                                            </span>
                                                            <span
                                                                className={cn(
                                                                    'mt-1 flex min-w-0 flex-wrap gap-1',
                                                                    'text-xs text-muted-foreground sm:hidden',
                                                                )}
                                                            >
                                                                <span className="max-w-full truncate">
                                                                    {sectionTypeLabel(
                                                                        section.section_type,
                                                                    )}
                                                                </span>
                                                                <span>·</span>
                                                                <span className="max-w-full truncate">
                                                                    {accountTypeLabel(
                                                                        section.account_type_filter,
                                                                    )}
                                                                </span>
                                                                <span>·</span>
                                                                <span className="max-w-full truncate">
                                                                    {subTypeLabel(
                                                                        section.account_sub_type_filter,
                                                                    )}
                                                                </span>
                                                                <span>·</span>
                                                                <span className="max-w-full truncate">
                                                                    {signLabel(
                                                                        section.sign_convention,
                                                                    )}
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
                                                            {accountTypeLabel(
                                                                section.account_type_filter,
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="hidden sm:table-cell">
                                                            {subTypeLabel(
                                                                section.account_sub_type_filter,
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="hidden sm:table-cell">
                                                            {signLabel(
                                                                section.sign_convention,
                                                            )}
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
