import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { cn, formatCurrency } from '@/lib/utils';
import { useQuery } from '@tanstack/react-query';
import type { ReactNode } from 'react';
import { Helmet } from 'react-helmet-async';
import { useSearchParams } from 'react-router-dom';

import type { FinancialReportFiscalYear } from './FinancialReportPageShell';

type BreadcrumbItem = {
    title: string;
    href: string;
};

export interface FinancialTableRow {
    id: number | string;
    code: string;
    name: string;
    type: string;
    level: number;
    parent_id: number | null;
}

type SingleYearFinancialReportResponse<TReport> = {
    fiscalYears: FinancialReportFiscalYear[];
    selectedYearId: number;
    report: TReport;
};

type SingleYearFinancialReportPageOptions<TReport> = {
    queryKey: string;
    endpoint: string;
    emptyReport: TReport;
};

type FinancialAmountColumn<TItem extends FinancialTableRow> = {
    header: string;
    total: number;
    value: (item: TItem) => number;
};

type SingleYearFinancialReportPageShellProps = {
    title: string;
    path: string;
    fiscalYears: FinancialReportFiscalYear[];
    selectedYearId: number;
    onYearChange: (value: string) => void;
    headerMeta?: ReactNode;
    preContent?: ReactNode;
    isLoading?: boolean;
    hasError?: boolean;
    children?: ReactNode;
};

type FinancialTableCardProps<TItem extends FinancialTableRow> = {
    title: string;
    items: TItem[];
    amountColumns: FinancialAmountColumn<TItem>[];
    emptyMessage: string;
    scrollAreaClassName: string;
    footerClassName?: string;
    footerLabel?: string;
    footerColSpan?: number;
    footerLeadingCell?: ReactNode;
    children?: ReactNode;
};

const buildBreadcrumbs = (title: string, path: string): BreadcrumbItem[] => [
    { title: 'Reports', href: '#' },
    { title, href: path },
];

export function useSingleYearReportSearchParams() {
    const [searchParams, setSearchParams] = useSearchParams();
    const urlYearId = searchParams.get('fiscal_year_id');

    const handleYearChange = (value: string) => {
        setSearchParams({ fiscal_year_id: value });
    };

    return {
        urlYearId,
        handleYearChange,
    };
}

export function useSingleYearFinancialReportQuery<TReport>(
    queryKey: string,
    endpoint: string,
    urlYearId: string | null,
) {
    return useQuery<SingleYearFinancialReportResponse<TReport>>({
        queryKey: [queryKey, urlYearId],
        queryFn: async () => {
            const params = new URLSearchParams();

            if (urlYearId) {
                params.append('fiscal_year_id', urlYearId);
            }

            const response = await axios.get(
                `/api/reports/${endpoint}?${params.toString()}`,
            );

            return response.data;
        },
    });
}

export function resolveSelectedFiscalYear(
    fiscalYears: FinancialReportFiscalYear[],
    selectedYearId: number,
) {
    return fiscalYears.find((fy) => fy.id === selectedYearId);
}

export function useSingleYearFinancialReportPage<TReport>({
    queryKey,
    endpoint,
    emptyReport,
}: SingleYearFinancialReportPageOptions<TReport>) {
    const { urlYearId, handleYearChange } = useSingleYearReportSearchParams();
    const { data, isLoading, error } =
        useSingleYearFinancialReportQuery<TReport>(
            queryKey,
            endpoint,
            urlYearId,
        );

    const fiscalYears = Array.isArray(data?.fiscalYears)
        ? data.fiscalYears
        : [];
    const selectedYearId = data?.selectedYearId || 0;
    const report = data?.report || emptyReport;
    const selectedFiscalYear = resolveSelectedFiscalYear(
        fiscalYears,
        selectedYearId,
    );

    return {
        fiscalYears,
        selectedYearId,
        report,
        selectedFiscalYear,
        handleYearChange,
        isLoading,
        error,
    };
}

export function SingleYearFinancialReportPageShell({
    title,
    path,
    fiscalYears,
    selectedYearId,
    onYearChange,
    headerMeta,
    preContent,
    isLoading = false,
    hasError = false,
    children,
}: Readonly<SingleYearFinancialReportPageShellProps>) {
    return (
        <AppLayout breadcrumbs={buildBreadcrumbs(title, path)}>
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
                        <div className="w-full sm:w-[240px]">
                            <Select
                                value={String(selectedYearId)}
                                onValueChange={onYearChange}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Fiscal Year" />
                                </SelectTrigger>
                                <SelectContent>
                                    {fiscalYears.map((fiscalYear) => (
                                        <SelectItem
                                            key={fiscalYear.id}
                                            value={String(fiscalYear.id)}
                                        >
                                            {fiscalYear.name} (
                                            {fiscalYear.status})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    {preContent}
                    {children}
                </div>
            )}
        </AppLayout>
    );
}

export function FinancialTableCard<TItem extends FinancialTableRow>({
    title,
    items,
    amountColumns,
    emptyMessage,
    scrollAreaClassName,
    footerClassName,
    footerLabel = 'Total',
    footerColSpan = 2,
    footerLeadingCell,
    children,
}: Readonly<FinancialTableCardProps<TItem>>) {
    const parentIds = new Set(
        items
            .map((item) => item.parent_id)
            .filter((id): id is number => id != null),
    );

    return (
        <Card className="flex-1">
            <CardHeader>
                <CardTitle>{title}</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="overflow-hidden rounded-md border">
                    <ScrollArea className={scrollAreaClassName}>
                        <Table className="min-w-[760px]">
                            <TableHeader className="sticky top-0 z-10 bg-background">
                                <TableRow>
                                    <TableHead className="w-[120px]">
                                        Code
                                    </TableHead>
                                    <TableHead>Account Name</TableHead>
                                    <TableHead className="hidden w-[120px] md:table-cell">
                                        Type
                                    </TableHead>
                                    {amountColumns.map((column) => (
                                        <TableHead
                                            key={column.header}
                                            className="text-right tabular-nums"
                                        >
                                            {column.header}
                                        </TableHead>
                                    ))}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {items.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={3 + amountColumns.length}
                                            className="h-24 text-center text-muted-foreground"
                                        >
                                            {emptyMessage}
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    items.map((item) => {
                                        const hasChildren =
                                            typeof item.id === 'number' &&
                                            parentIds.has(item.id);

                                        return (
                                            <TableRow
                                                key={item.id}
                                                className={cn(
                                                    'odd:bg-muted/10',
                                                    hasChildren &&
                                                        'bg-muted/20 font-medium',
                                                )}
                                            >
                                                <TableCell className="font-mono text-xs text-muted-foreground">
                                                    {item.code}
                                                </TableCell>
                                                <TableCell>
                                                    <div
                                                        className="truncate"
                                                        style={{
                                                            paddingLeft: `${Math.max(0, item.level - 1) * 1.25}rem`,
                                                        }}
                                                    >
                                                        {item.name}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="hidden text-muted-foreground capitalize md:table-cell">
                                                    {item.type}
                                                </TableCell>
                                                {amountColumns.map((column) => {
                                                    const value =
                                                        column.value(item);

                                                    return (
                                                        <TableCell
                                                            key={column.header}
                                                            className="text-right tabular-nums"
                                                        >
                                                            {value === 0
                                                                ? '-'
                                                                : formatCurrency(
                                                                      value,
                                                                  )}
                                                        </TableCell>
                                                    );
                                                })}
                                            </TableRow>
                                        );
                                    })
                                )}
                            </TableBody>
                            {items.length > 0 && (
                                <TableFooter>
                                    <TableRow>
                                        <TableCell
                                            colSpan={footerColSpan}
                                            className="text-right font-semibold"
                                        >
                                            {footerLabel}
                                        </TableCell>
                                        {footerLeadingCell ?? (
                                            <TableCell className="hidden md:table-cell" />
                                        )}
                                        {amountColumns.map((column) => (
                                            <TableCell
                                                key={column.header}
                                                className={cn(
                                                    'text-right font-semibold tabular-nums',
                                                    footerClassName,
                                                )}
                                            >
                                                {formatCurrency(column.total)}
                                            </TableCell>
                                        ))}
                                    </TableRow>
                                </TableFooter>
                            )}
                        </Table>
                        <ScrollBar orientation="horizontal" />
                    </ScrollArea>
                </div>
                {children}
            </CardContent>
        </Card>
    );
}
