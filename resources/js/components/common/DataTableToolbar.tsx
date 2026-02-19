'use client';

import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Table } from '@tanstack/react-table';
import {
    ChevronDown,
    Download,
    Filter,
    Loader2,
    PlusCircle,
} from 'lucide-react';
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
    hasData: boolean;

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
    hasData,
    table,
}: DataTableToolbarProps<T>) {
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
        <>
            {/* Toolbar */}
            <div className="items-center justify-between py-4 lg:flex">
                <div className="mb-2 flex items-center space-x-2">
                    <Input
                        placeholder={searchPlaceholder}
                        value={searchValue}
                        onChange={handleSearchChange}
                        onKeyDown={handleSearchKeyDown}
                        className="max-w-sm border-border bg-background placeholder:text-muted-foreground"
                    />
                </div>
                <div className="mb-2 flex items-center space-x-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => setIsFilterModalOpen(true)}
                        aria-label="Filters"
                    >
                        <Filter className="mr-2 h-4 w-4" />
                        Filters
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

                    <Button size="sm" onClick={onAdd}>
                        <PlusCircle className="mr-2 h-4 w-4" />
                        Add
                    </Button>

                    {extraActions}

                    <Button
                        variant="outline"
                        size="sm"
                        onClick={onExport}
                        disabled={!hasData || exporting}
                    >
                        {exporting ? (
                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        ) : (
                            <Download className="mr-2 h-4 w-4" />
                        )}
                        Export
                    </Button>

                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="outline" size="sm">
                                Columns <ChevronDown className="ml-2 h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
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
        </>
    );
}
