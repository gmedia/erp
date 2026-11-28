import { AsyncSelect } from '@/components/common/AsyncSelect';
import { PageHeader } from '@/components/common/PageHeader';
import { ReportPageDescription } from '@/components/common/ReportDataTablePage';
import { LabelText } from '@/components/common/TranslatedText';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/contexts/i18n-context';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { formatStatusLabel } from '@/lib/status-badge';
import { useQuery } from '@tanstack/react-query';
import type { ReactNode } from 'react';
import { Helmet } from 'react-helmet-async';
import { useSearchParams } from 'react-router-dom';

type BreadcrumbItem = {
    title: string;
    href: string;
};

export type FinancialReportFiscalYear = {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    status: string;
};

import type { ComputedSection } from './ComputedSectionsCard';

type ComparisonReportResponse<TReport> = {
    fiscalYears: FinancialReportFiscalYear[];
    selectedYearId: number;
    comparisonYearId?: number;
    report: TReport;
    computed_sections: ComputedSection[];
};

type ComparisonFinancialReportPageOptions<TReport> = {
    queryKey: string;
    endpoint: string;
    emptyReport: TReport;
};

type FinancialReportPageShellProps = {
    title: string;
    titleKey?: string;
    description?: string;
    descriptionKey?: string;
    path: string;
    fiscalYears: FinancialReportFiscalYear[];
    selectedYearId: number;
    comparisonYearId?: number;
    onYearChange: (value: string) => void;
    onComparisonChange: (value: string) => void;
    branchId?: string | null;
    onBranchChange?: (value: string) => void;
    departmentId?: string | null;
    onDepartmentChange?: (value: string) => void;
    headerMeta?: ReactNode;
    headerActions?: ReactNode;
    isLoading?: boolean;
    hasError?: boolean;
    children?: ReactNode;
};

export const translateOr = (
    t: (key: string) => string,
    key: string,
    fallback: string,
): string => {
    const translated = t(key);
    return translated === key ? fallback : translated;
};

const buildBreadcrumbs = (
    t: (key: string) => string,
    title: string,
    path: string,
): BreadcrumbItem[] => [
    { title: t('reports.breadcrumbGroup'), href: '#' },
    { title, href: path },
];

export function useComparisonReportSearchParams() {
    const [searchParams, setSearchParams] = useSearchParams();
    const urlYearId = searchParams.get('fiscal_year_id');
    const urlComparisonId = searchParams.get('comparison_year_id');
    const urlBranchId = searchParams.get('branch_id');
    const urlDepartmentId = searchParams.get('department_id');

    const handleYearChange = (value: string) => {
        const params: Record<string, string> = { fiscal_year_id: value };

        if (urlComparisonId) {
            params.comparison_year_id = urlComparisonId;
        }

        if (urlBranchId) {
            params.branch_id = urlBranchId;
        }

        if (urlDepartmentId) {
            params.department_id = urlDepartmentId;
        }

        setSearchParams(params);
    };

    const handleComparisonChange = (value: string, selectedYearId: number) => {
        const params: Record<string, string> = {
            fiscal_year_id: String(selectedYearId),
        };

        if (value !== 'none') {
            params.comparison_year_id = value;
        }

        if (urlBranchId) {
            params.branch_id = urlBranchId;
        }

        if (urlDepartmentId) {
            params.department_id = urlDepartmentId;
        }

        setSearchParams(params);
    };

    const handleBranchChange = (value: string, selectedYearId: number) => {
        const params: Record<string, string> = {
            fiscal_year_id: String(selectedYearId),
        };

        if (urlComparisonId) {
            params.comparison_year_id = urlComparisonId;
        }

        if (value) {
            params.branch_id = value;
        }

        if (urlDepartmentId) {
            params.department_id = urlDepartmentId;
        }

        setSearchParams(params);
    };

    const handleDepartmentChange = (value: string, selectedYearId: number) => {
        const params: Record<string, string> = {
            fiscal_year_id: String(selectedYearId),
        };

        if (urlComparisonId) {
            params.comparison_year_id = urlComparisonId;
        }

        if (urlBranchId) {
            params.branch_id = urlBranchId;
        }

        if (value) {
            params.department_id = value;
        }

        setSearchParams(params);
    };

    return {
        urlYearId,
        urlComparisonId,
        urlBranchId,
        urlDepartmentId,
        handleYearChange,
        handleComparisonChange,
        handleBranchChange,
        handleDepartmentChange,
    };
}

export function useComparisonFinancialReportQuery<TReport>(
    queryKey: string,
    endpoint: string,
    urlYearId: string | null,
    urlComparisonId: string | null,
    urlBranchId: string | null,
    urlDepartmentId: string | null = null,
) {
    return useQuery<ComparisonReportResponse<TReport>>({
        queryKey: [
            queryKey,
            urlYearId,
            urlComparisonId,
            urlBranchId,
            urlDepartmentId,
        ],
        queryFn: async () => {
            const params = new URLSearchParams();

            if (urlYearId) {
                params.append('fiscal_year_id', urlYearId);
            }

            if (urlComparisonId) {
                params.append('comparison_year_id', urlComparisonId);
            }

            if (urlBranchId) {
                params.append('branch_id', urlBranchId);
            }

            if (urlDepartmentId) {
                params.append('department_id', urlDepartmentId);
            }

            const response = await axios.get(
                `/api/reports/${endpoint}?${params.toString()}`,
            );

            return response.data;
        },
    });
}

export function resolveComparisonFiscalYears(
    fiscalYears: FinancialReportFiscalYear[],
    selectedYearId: number,
    comparisonYearId?: number,
) {
    return {
        selectedFiscalYear: fiscalYears.find((fy) => fy.id === selectedYearId),
        selectedComparisonFiscalYear: comparisonYearId
            ? fiscalYears.find((fy) => fy.id === comparisonYearId)
            : undefined,
    };
}

export function useComparisonFinancialReportPage<TReport>({
    queryKey,
    endpoint,
    emptyReport,
}: ComparisonFinancialReportPageOptions<TReport>) {
    const {
        urlYearId,
        urlComparisonId,
        urlBranchId,
        urlDepartmentId,
        handleYearChange,
        handleComparisonChange,
        handleBranchChange,
        handleDepartmentChange,
    } = useComparisonReportSearchParams();

    const { data, isLoading, error } =
        useComparisonFinancialReportQuery<TReport>(
            queryKey,
            endpoint,
            urlYearId,
            urlComparisonId,
            urlBranchId,
            urlDepartmentId,
        );

    const fiscalYears = data?.fiscalYears || [];
    const selectedYearId = data?.selectedYearId || 0;
    const comparisonYearId = data?.comparisonYearId;
    const report = data?.report ?? emptyReport;
    const computedSections = data?.computed_sections || [];
    const { selectedFiscalYear, selectedComparisonFiscalYear } =
        resolveComparisonFiscalYears(
            fiscalYears,
            selectedYearId,
            comparisonYearId,
        );

    return {
        fiscalYears,
        selectedYearId,
        comparisonYearId,
        branchId: urlBranchId,
        departmentId: urlDepartmentId,
        report,
        computedSections,
        selectedFiscalYear,
        selectedComparisonFiscalYear,
        handleYearChange,
        handleComparisonChange,
        handleBranchChange,
        handleDepartmentChange,
        isLoading,
        error,
    };
}

type FinancialReportHeaderMetaProps = {
    fiscalYear?: FinancialReportFiscalYear;
    comparisonFiscalYear?: FinancialReportFiscalYear;
    showComparisonBadge?: boolean;
    children?: ReactNode;
};

export function FinancialReportHeaderMeta({
    fiscalYear,
    comparisonFiscalYear,
    showComparisonBadge = false,
    children,
}: Readonly<FinancialReportHeaderMetaProps>) {
    const { t } = useTranslation();

    if (!fiscalYear && !showComparisonBadge && !children) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
            {fiscalYear && (
                <span>
                    {fiscalYear.name} · {formatStatusLabel(fiscalYear.status)}
                </span>
            )}
            {showComparisonBadge && (
                <Badge variant="outline">
                    {comparisonFiscalYear
                        ? t('reports.financial.compare_with', {
                              year: comparisonFiscalYear.name,
                          })
                        : t('reports.financial.compare_none')}
                </Badge>
            )}
            {children}
        </div>
    );
}

export function FinancialReportPageShell({
    title: fallbackTitle,
    titleKey,
    description,
    descriptionKey,
    path,
    fiscalYears,
    selectedYearId,
    comparisonYearId,
    onYearChange,
    onComparisonChange,
    branchId,
    onBranchChange,
    departmentId,
    onDepartmentChange,
    headerMeta,
    headerActions,
    isLoading = false,
    hasError = false,
    children,
}: Readonly<FinancialReportPageShellProps>) {
    const { t } = useTranslation();
    const title = titleKey
        ? translateOr(t, titleKey, fallbackTitle)
        : fallbackTitle;
    const breadcrumbs = buildBreadcrumbs(t, title, path);

    let content: ReactNode;
    if (isLoading) {
        content = (
            <div className="flex h-full items-center justify-center p-4">
                {t('reports.financial.loading')}
            </div>
        );
    } else if (hasError) {
        content = (
            <div className="flex h-full items-center justify-center p-4 text-destructive">
                {t('reports.financial.error')}
            </div>
        );
    } else {
        content = (
            <div className="flex w-full flex-col gap-3 p-4">
                <PageHeader
                    title={title}
                    description={
                        descriptionKey ? (
                            <ReportPageDescription
                                descriptionKey={descriptionKey}
                            />
                        ) : (
                            description
                        )
                    }
                    meta={headerMeta}
                    actions={
                        <>
                            <div className="flex w-full flex-col gap-1 sm:w-[220px]">
                                <span className="text-xs font-medium text-muted-foreground">
                                    {translateOr(
                                        t,
                                        'column.fiscal_year',
                                        'Fiscal Year',
                                    )}
                                </span>
                                <Select
                                    value={String(selectedYearId)}
                                    onValueChange={onYearChange}
                                >
                                    <SelectTrigger
                                        aria-label={translateOr(
                                            t,
                                            'column.fiscal_year',
                                            'Fiscal Year',
                                        )}
                                    >
                                        <SelectValue
                                            placeholder={translateOr(
                                                t,
                                                'column.fiscal_year',
                                                'Fiscal Year',
                                            )}
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {fiscalYears.map((fiscalYear) => (
                                            <SelectItem
                                                key={fiscalYear.id}
                                                value={String(fiscalYear.id)}
                                            >
                                                {fiscalYear.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex w-full flex-col gap-1 sm:w-[220px]">
                                <span className="text-xs font-medium text-muted-foreground">
                                    <LabelText
                                        label={{
                                            key: 'column.compare_fiscal_year',
                                            fallback: 'Compare',
                                        }}
                                    />
                                </span>
                                <Select
                                    value={
                                        comparisonYearId
                                            ? String(comparisonYearId)
                                            : 'none'
                                    }
                                    onValueChange={onComparisonChange}
                                >
                                    <SelectTrigger
                                        aria-label={translateOr(
                                            t,
                                            'column.compare_fiscal_year',
                                            'Compare fiscal year',
                                        )}
                                        className={
                                            comparisonYearId
                                                ? undefined
                                                : 'text-muted-foreground'
                                        }
                                    >
                                        <SelectValue
                                            placeholder={translateOr(
                                                t,
                                                'column.compare_with',
                                                'Compare With...',
                                            )}
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            value="none"
                                            className="text-muted-foreground"
                                        >
                                            {t('reports.financial.none')}
                                        </SelectItem>
                                        {fiscalYears
                                            .filter(
                                                (fiscalYear) =>
                                                    fiscalYear.id !==
                                                    selectedYearId,
                                            )
                                            .map((fiscalYear) => (
                                                <SelectItem
                                                    key={fiscalYear.id}
                                                    value={String(
                                                        fiscalYear.id,
                                                    )}
                                                >
                                                    {fiscalYear.name}
                                                </SelectItem>
                                            ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            {onBranchChange && (
                                <div className="flex w-full flex-col gap-1 sm:w-[200px]">
                                    <span className="text-xs font-medium text-muted-foreground">
                                        <LabelText
                                            label={{
                                                key: 'column.branch',
                                                fallback: 'Branch',
                                            }}
                                        />
                                    </span>
                                    <AsyncSelect
                                        url="/api/branches"
                                        placeholder={translateOr(
                                            t,
                                            'column.all_branches',
                                            'All Branches',
                                        )}
                                        value={branchId ?? ''}
                                        onValueChange={onBranchChange}
                                        label={translateOr(
                                            t,
                                            'column.branch',
                                            'Branch',
                                        )}
                                    />
                                </div>
                            )}
                            {onDepartmentChange && (
                                <div className="flex w-full flex-col gap-1 sm:w-[200px]">
                                    <span className="text-xs font-medium text-muted-foreground">
                                        <LabelText
                                            label={{
                                                key: 'column.department',
                                                fallback: 'Department',
                                            }}
                                        />
                                    </span>
                                    <AsyncSelect
                                        url="/api/departments"
                                        placeholder={translateOr(
                                            t,
                                            'column.all_departments',
                                            'All Departments',
                                        )}
                                        value={departmentId ?? ''}
                                        onValueChange={onDepartmentChange}
                                        leadingOption={{
                                            value: 'none',
                                            label: translateOr(
                                                t,
                                                'column.without_department',
                                                'Without Department',
                                            ),
                                        }}
                                        label={translateOr(
                                            t,
                                            'column.department',
                                            'Department',
                                        )}
                                    />
                                </div>
                            )}
                            {headerActions}
                        </>
                    }
                />

                {children}
            </div>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Helmet>
                <title>{title}</title>
            </Helmet>

            {content}
        </AppLayout>
    );
}
