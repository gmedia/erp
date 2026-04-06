import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
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

type FinancialReportPageShellProps = {
    title: string;
    path: string;
    fiscalYears: FinancialReportFiscalYear[];
    selectedYearId: number;
    comparisonYearId?: number;
    onYearChange: (value: string) => void;
    onComparisonChange: (value: string) => void;
    headerMeta?: ReactNode;
    isLoading?: boolean;
    hasError?: boolean;
    children?: ReactNode;
};

const buildBreadcrumbs = (title: string, path: string): BreadcrumbItem[] => [
    { title: 'Reports', href: '#' },
    { title, href: path },
];

export function useComparisonReportSearchParams() {
    const [searchParams, setSearchParams] = useSearchParams();
    const urlYearId = searchParams.get('fiscal_year_id');
    const urlComparisonId = searchParams.get('comparison_year_id');

    const handleYearChange = (value: string) => {
        const params: Record<string, string> = { fiscal_year_id: value };

        if (urlComparisonId) {
            params.comparison_year_id = urlComparisonId;
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

        setSearchParams(params);
    };

    return {
        urlYearId,
        urlComparisonId,
        handleYearChange,
        handleComparisonChange,
    };
}

export function FinancialReportPageShell({
    title,
    path,
    fiscalYears,
    selectedYearId,
    comparisonYearId,
    onYearChange,
    onComparisonChange,
    headerMeta,
    isLoading = false,
    hasError = false,
    children,
}: Readonly<FinancialReportPageShellProps>) {
    const breadcrumbs = buildBreadcrumbs(title, path);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Helmet>
                <title>{title}</title>
            </Helmet>

            {isLoading ? (
                <div className="flex h-full items-center justify-center p-4">
                    Loading report...
                </div>
            ) : hasError ? (
                <div className="flex h-full items-center justify-center p-4 text-destructive">
                    Error loading report.
                </div>
            ) : (
                <div className="flex h-full flex-1 flex-col gap-4 p-4">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex flex-col gap-1">
                            <h1 className="text-2xl font-bold tracking-tight">
                                {title}
                            </h1>
                            {headerMeta}
                        </div>
                        <div className="flex flex-col gap-3 sm:flex-row sm:gap-4">
                            <div className="w-full sm:w-[220px]">
                                <Select
                                    value={String(selectedYearId)}
                                    onValueChange={onYearChange}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Fiscal Year" />
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
                            <div className="w-full sm:w-[220px]">
                                <Select
                                    value={
                                        comparisonYearId
                                            ? String(comparisonYearId)
                                            : 'none'
                                    }
                                    onValueChange={onComparisonChange}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Compare With..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            None
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
                        </div>
                    </div>

                    {children}
                </div>
            )}
        </AppLayout>
    );
}
