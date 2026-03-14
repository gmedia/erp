'use client';

import { GenericActions } from '@/components/common/ActionsDropdown';
import { DataTablePagination } from '@/components/common/DataTablePagination';
import { DataTableToolbar } from '@/components/common/DataTableToolbar';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { CustomTableMeta } from '@/utils/columns';
import {
    ColumnDef,
    SortingState,
    VisibilityState,
    flexRender,
    getCoreRowModel,
    getSortedRowModel,
    useReactTable,
} from '@tanstack/react-table';

import { useExport } from '@/hooks/useExport';
import * as React from 'react';
import type { FieldDescriptor } from './filters';

// Helper function to safely extract placeholder from filter fields
function getPlaceholderFromFilterFields(
    filterFields: FieldDescriptor[],
): string {
    if (filterFields.length === 0) return 'Search...';

    const firstField = filterFields[0];
    const component = firstField.component;

    if (React.isValidElement(component)) {
        const placeholder = (component.props as { placeholder?: string })
            ?.placeholder;
        return placeholder || 'Search...';
    }

    return 'Search...';
}

export interface DataTableProps<T> {
    columns: ColumnDef<T>[];
    data: T[];
    pagination: {
        page: number;
        per_page: number;
        total: number;
        last_page: number;
        from: number;
        to: number;
    };
    onPageChange: (page: number) => void;
    onPageSizeChange: (per_page: number) => void;
    onSearchChange: (search: string) => void;
    isLoading?: boolean;
    filterValue?: string;
    filters?: Record<string, string | undefined>;
    onFilterChange: (filters: Record<string, string | undefined>) => void;
    onResetFilters: () => void;
    /** Optional endpoint for export requests */
    exportEndpoint?: string;
    /** Optional fields to render inside the filter modal */
    filterFields?: FieldDescriptor[];
    /** Optional callbacks for row actions */
    onAdd?: () => void;
    onEdit?: (item: T) => void;
    onDelete?: (item: T) => void;
    onView?: (item: T) => void;
    /** Optional extra items for the actions dropdown */
    extraActionItems?: React.ReactNode[];
    /** Optional extra items for the toolbar */
    extraToolbarActions?: React.ReactNode;
    /** Optional entity name for default search placeholder */
    entityName?: string;
}

/**
 * DataTable – a reusable data table component with built-in filtering, pagination, and actions.
 *
 * Features:
 * - Column definitions with custom renderers
 * - Built-in pagination and page size controls
 * - Search and advanced filtering
 * - Export functionality
 * - Row actions (add, edit, delete, view)
 * - Loading states and empty states
 */
export function DataTable<T>({
    columns,
    data,
    pagination,
    onPageChange,
    onPageSizeChange,
    onSearchChange,
    isLoading,
    filterValue = '',
    filters = {},
    onFilterChange,
    onResetFilters,
    exportEndpoint = '/api/export',
    filterFields = [],
    onAdd,
    onEdit,
    onDelete,
    onView,
    extraActionItems,
    extraToolbarActions,
    entityName,
}: Readonly<DataTableProps<T>>) {
    const [sorting, setSorting] = React.useState<SortingState>([]);
    const [columnVisibility, setColumnVisibility] =
        React.useState<VisibilityState>({});
    const [rowSelection, setRowSelection] = React.useState({});
    const [searchValue, setSearchValue] = React.useState(filterValue);
    const [isFilterModalOpen, setIsFilterModalOpen] = React.useState(false);
    // Use the export hook
    const { exporting, exportData } = useExport({ endpoint: exportEndpoint });

    // Temporary filter state for the modal
    const [tempFilters, setTempFilters] = React.useState<
        Record<string, string>
    >(
        Object.fromEntries(
            Object.entries(filters).filter(
                ([, v]) => v !== undefined && v !== '',
            ),
        ) as Record<string, string>,
    );

    // Add default search field if no filterFields provided and entityName is given
    const defaultFilterFields = React.useMemo(() => {
        if (filterFields.length === 0 && entityName) {
            return [
                {
                    name: 'search',
                    label: 'Search',
                    component: (
                        <Input
                            placeholder={`Search ${entityName.toLowerCase()}s...`}
                        />
                    ),
                },
            ];
        }
        return filterFields;
    }, [filterFields, entityName]);

    // Remove unused handleSortingChange and use onSortingChange instead

    // Memoize export handler
    const handleExport = React.useCallback(() => {
        exportData(filters);
    }, [exportData, filters]);

    // Sync temporary filters with external filters when they change or when the modal opens
    React.useEffect(() => {
        if (isFilterModalOpen) {
            setTempFilters(
                Object.fromEntries(
                    Object.entries(filters).filter(
                        ([, v]) => v !== undefined && v !== '',
                    ),
                ) as Record<string, string>,
            );
        }
    }, [filters, isFilterModalOpen]);

    // Enhance columns with a generic actions column when callbacks are provided
    const columnsWithActions = React.useMemo(() => {
        if (!onEdit && !onDelete && !onView && !extraActionItems) {
            return columns;
        }
        return columns.map((col) => {
            if (col.id === 'actions') {
                const viewPath = (col.meta as CustomTableMeta<T> | undefined)
                    ?.viewPath;
                return {
                    ...col,
                    cell: ({ row }: { row: { original: T } }) => (
                        <GenericActions<T>
                            item={row.original}
                            onView={onView}
                            onEdit={onEdit!}
                            onDelete={onDelete!}
                            extraItems={extraActionItems}
                            viewUrl={
                                viewPath ? viewPath(row.original) : undefined
                            }
                        />
                    ),
                };
            }
            return col;
        });
    }, [columns, onEdit, onDelete, onView, extraActionItems]);

    const table = useReactTable({
        data,
        columns: columnsWithActions,
        state: {
            sorting,
            columnVisibility,
            rowSelection,
        },
        onSortingChange: (updater) => {
            const nextSorting =
                typeof updater === 'function' ? updater(sorting) : updater;
            setSorting(nextSorting);

            if (nextSorting.length > 0) {
                onFilterChange({
                    ...filters,
                    sort_by: nextSorting[0].id,
                    sort_direction: nextSorting[0].desc ? 'desc' : 'asc',
                });
            } else {
                const newFilters = { ...filters };
                delete newFilters.sort_by;
                delete newFilters.sort_direction;
                onFilterChange(newFilters);
            }
        },
        onColumnVisibilityChange: setColumnVisibility,
        onRowSelectionChange: setRowSelection,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
        manualPagination: true,
        meta: {
            onView,
            onEdit,
            onDelete,
        },
    });

    React.useEffect(() => {
        setSearchValue(filterValue);
    }, [filterValue]);

    const handlePageChange = (page: number) => {
        onPageChange(page);
    };

    const handlePageSizeChange = (per_page: string) => {
        onPageSizeChange(Number(per_page));
    };

    const handleApplyFilters = () => {
        onFilterChange(tempFilters as Record<string, string | undefined>);
        setIsFilterModalOpen(false);
    };

    const loadingRows = ['loading-row-1', 'loading-row-2', 'loading-row-3', 'loading-row-4', 'loading-row-5'];
    const visibleRows = table.getRowModel().rows;

    let tableContent: React.ReactNode;
    if (isLoading) {
        tableContent = loadingRows.map((rowKey) => (
            <TableRow key={rowKey}>
                {columns.map((column) => {
                    const columnKey =
                        'id' in column && column.id
                            ? String(column.id)
                            : 'accessorKey' in column && column.accessorKey
                              ? String(column.accessorKey)
                              : typeof column.header === 'string'
                                ? column.header
                                : 'loading-column';

                    return (
                        <TableCell key={`${rowKey}-${columnKey}`} className="border-border">
                            <Skeleton className="h-4 w-full bg-muted" />
                        </TableCell>
                    );
                })}
            </TableRow>
        ));
    } else if (visibleRows.length > 0) {
        tableContent = visibleRows.map((row) => (
            <TableRow
                key={row.id}
                data-state={row.getIsSelected() && 'selected'}
                className="hover:bg-muted/50"
            >
                {row.getVisibleCells().map((cell) => (
                    <TableCell key={cell.id} className="border-border">
                        {flexRender(cell.column.columnDef.cell, cell.getContext())}
                    </TableCell>
                ))}
            </TableRow>
        ));
    } else {
        tableContent = (
            <TableRow>
                <TableCell
                    colSpan={columns.length}
                    className="h-24 text-center text-muted-foreground"
                >
                    No results.
                </TableCell>
            </TableRow>
        );
    }

    return (
        <div className="w-full bg-background text-foreground">
            <DataTableToolbar
                searchValue={searchValue}
                onSearchChange={setSearchValue}
                onSearchSubmit={onSearchChange}
                searchPlaceholder={
                    defaultFilterFields && defaultFilterFields.length > 0
                        ? getPlaceholderFromFilterFields(defaultFilterFields)
                        : 'Search...'
                }
                filterFields={defaultFilterFields}
                tempFilters={tempFilters}
                onTempFiltersChange={setTempFilters}
                onApplyFilters={handleApplyFilters}
                onResetFilters={() => {
                    const cleaned = Object.fromEntries(
                        Object.entries(filters).filter(
                            ([, v]) => v !== undefined && v !== '',
                        ),
                    ) as Record<string, string>;
                    setTempFilters(cleaned);
                }}
                onClearAllFilters={() => {
                    onResetFilters();
                    setTempFilters({});
                    setIsFilterModalOpen(false);
                }}
                isFilterModalOpen={isFilterModalOpen}
                setIsFilterModalOpen={setIsFilterModalOpen}
                onAdd={onAdd}
                extraActions={extraToolbarActions}
                onExport={handleExport}
                exporting={exporting}
                hasData={data.length > 0}
                table={table}
            />

            {/* Table */}
            <div className="overflow-hidden rounded-md border border-border">
                <Table>
                    <TableHeader className="bg-muted">
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map((header) => (
                                    <TableHead
                                        key={header.id}
                                        className="border-border select-none"
                                    >
                                        {header.isPlaceholder
                                            ? null
                                            : flexRender(
                                                  header.column.columnDef
                                                      .header,
                                                  header.getContext(),
                                              )}
                                    </TableHead>
                                ))}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>{tableContent}</TableBody>
                </Table>
            </div>

            {/* Pagination */}
            <DataTablePagination
                pagination={pagination}
                onPageChange={handlePageChange}
                onPageSizeChange={(per_page: number) =>
                    handlePageSizeChange(per_page.toString())
                }
            />
        </div>
    );
}
