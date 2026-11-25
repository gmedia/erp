'use client';

import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useTranslation } from '@/contexts/i18n-context';
import { Table } from '@tanstack/react-table';
import { Columns3, Download, Filter, Loader2, PlusCircle } from 'lucide-react';
import * as React from 'react';
import { FilterModal } from './FilterModal';
import type { FieldDescriptor } from './filters';

interface DataTableToolbarProps<T> {
    // Search functionality
    searchValue: string;
    onSearchChange: (value: string) => void;
    onSearchSubmit: (value: string) => void;
    searchPlaceholder: string;

    // Filter functionality
    filterFields: FieldDescriptor[];
    tempFilters: Record<string, string>;
    onTempFiltersChange: (filters: Record<string, string>) => void;
    onApplyFilters: () => void;
    onResetFilters: () => void;
    onClearAllFilters: () => void;
    isFilterModalOpen: boolean;
    setIsFilterModalOpen: (open: boolean) => void;

    // Actions
    onAdd?: () => void;
    extraActions?: React.ReactNode;

    // Export functionality
    onExport: () => void;
    exporting: boolean;
    exportDisabled?: boolean;

    // Table instance for column visibility
    table: Table<T>;
}

/**
 * DataTableToolbar - Toolbar component for data tables
 *
 * Contains search input, filters, add button, export button, and column visibility controls.
 */
export function DataTableToolbar<T>({
    searchValue,
    onSearchChange,
    onSearchSubmit,
    searchPlaceholder,
    filterFields,
    tempFilters,
    onTempFiltersChange,
    onApplyFilters,
    onResetFilters,
    onClearAllFilters,
    isFilterModalOpen,
    setIsFilterModalOpen,
    onAdd,
    extraActions,
    onExport,
    exporting,
    exportDisabled = false,
    table,
}: Readonly<DataTableToolbarProps<T>>) {
    const { t } = useTranslation();
    const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        onSearchChange(e.target.value);
    };

    const handleSearchKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            onSearchSubmit(e.currentTarget.value);
        }
    };

    return (
        <div className="flex flex-wrap items-center gap-2 py-4">
            <Input
                placeholder={searchPlaceholder}
                value={searchValue}
                onChange={handleSearchChange}
                onKeyDown={handleSearchKeyDown}
                className="h-9 w-full min-w-0 border-border bg-background text-ellipsis placeholder:text-muted-foreground sm:max-w-sm sm:flex-1"
                data-testid="search-input"
            />

            {/* Mobile: kontrol dalam satu baris bisa di-scroll, bukan 2-3 baris (W4-15). */}
            <div className="ml-auto flex w-full flex-nowrap items-center gap-2 overflow-x-auto pb-1 sm:w-auto sm:overflow-visible sm:pb-0">
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setIsFilterModalOpen(true)}
                    aria-label={t('shell.filters')}
                    data-testid="filter-button"
                >
                    <Filter className="h-4 w-4 sm:mr-2" />
                    <span className="hidden sm:inline">
                        {t('shell.filters')}
                    </span>
                </Button>

                <FilterModal
                    open={isFilterModalOpen}
                    onOpenChange={setIsFilterModalOpen}
                    filterFields={filterFields}
                    tempFilters={tempFilters}
                    onTempFiltersChange={onTempFiltersChange}
                    onApply={onApplyFilters}
                    onReset={onResetFilters}
                    onClearAll={onClearAllFilters}
                />

                {onAdd && (
                    <Button
                        size="sm"
                        onClick={onAdd}
                        aria-label={t('shell.add')}
                        data-testid="add-button"
                    >
                        <PlusCircle className="h-4 w-4 sm:mr-2" />
                        <span className="hidden sm:inline">
                            {t('shell.add')}
                        </span>
                    </Button>
                )}

                {extraActions}

                <Button
                    variant="outline"
                    size="sm"
                    onClick={onExport}
                    disabled={exporting || exportDisabled}
                    aria-label={t('shell.export')}
                    data-testid="export-button"
                >
                    {exporting ? (
                        <Loader2 className="h-4 w-4 animate-spin sm:mr-2" />
                    ) : (
                        <Download className="h-4 w-4 sm:mr-2" />
                    )}
                    <span className="hidden sm:inline">
                        {t('shell.export')}
                    </span>
                </Button>

                <DropdownMenu>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    aria-label={t('shell.columns')}
                                    data-testid="columns-button"
                                >
                                    <Columns3 className="h-4 w-4 sm:mr-2" />
                                    <span className="hidden sm:inline">
                                        {t('shell.columns')}
                                    </span>
                                </Button>
                            </DropdownMenuTrigger>
                        </TooltipTrigger>
                        <TooltipContent>{t('shell.columns')}</TooltipContent>
                    </Tooltip>
                    <DropdownMenuContent
                        align="end"
                        className="border-border bg-background text-foreground"
                    >
                        {table
                            .getAllColumns()
                            .filter((col) => col.getCanHide())
                            .map((col) => (
                                <DropdownMenuCheckboxItem
                                    key={col.id}
                                    className="capitalize"
                                    checked={col.getIsVisible()}
                                    onCheckedChange={(value: boolean) =>
                                        col.toggleVisibility(!!value)
                                    }
                                >
                                    {col.id}
                                </DropdownMenuCheckboxItem>
                            ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    );
}
