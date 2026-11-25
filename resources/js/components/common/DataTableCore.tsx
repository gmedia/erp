'use client';

import { GenericActions } from '@/components/common/ActionsDropdown';
import { DataTablePagination } from '@/components/common/DataTablePagination';
import { DataTableToolbar } from '@/components/common/DataTableToolbar';
import { Button } from '@/components/ui/button';
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
import { cn } from '@/lib/utils';
import * as React from 'react';
import type { FieldDescriptor } from './filters';

// Tepi kiri kolom sticky: fade ke latar halaman agar kolom yang tertutup
// (scroll horizontal) memudar, bukan terpotong tajam di atas latar datar.
const STICKY_EDGE_SHADOW =
    'before:pointer-events-none before:absolute before:inset-y-0 before:-left-6 before:w-6 before:bg-linear-to-r before:from-transparent before:to-background';
// Sel body aksi wajib berlatar solid: `bg-inherit` mewarisi transparan dari
// <tr>, sehingga teks kolom lain tembus saat tabel di-scroll horizontal.
const STICKY_ACTIONS_CELL = [
    'sticky right-0 z-20 bg-background',
    'group-hover:bg-muted group-data-[state=selected]:bg-muted',
].join(' ');
const STICKY_ACTIONS_HEAD = `sticky right-0 z-30 bg-background`;
const STICKY_GRAND_TOTAL_CELL = `lg:sticky lg:right-14 lg:z-10 min-w-28 bg-background whitespace-nowrap ${STICKY_EDGE_SHADOW}`;
const STICKY_GRAND_TOTAL_HEAD = `lg:sticky lg:right-14 lg:z-20 min-w-28 bg-background whitespace-nowrap ${STICKY_EDGE_SHADOW}`;

function isActionsColumn(columnId: string): boolean {
    return columnId === 'actions' || columnId === 'actions_custom';
}

function stickyCellClass(
    columnId: string,
    hasGrandTotal: boolean,
    isBeforeActions = false,
): string | false {
    if (isActionsColumn(columnId)) {
        return cn(STICKY_ACTIONS_CELL, !hasGrandTotal && STICKY_EDGE_SHADOW);
    }
    if (columnId === 'grand_total' && hasGrandTotal) {
        return STICKY_GRAND_TOTAL_CELL;
    }
    if (isBeforeActions) {
        return CELL_BEFORE_STICKY_ACTIONS;
    }

    return false;
}

const CELL_BEFORE_STICKY_ACTIONS = 'pr-6';

function stickyColumnAttr(
    columnId: string,
    hasGrandTotal: boolean,
): 'grand_total' | 'actions' | undefined {
    if (columnId === 'grand_total' && hasGrandTotal) {
        return 'grand_total';
    }
    if (isActionsColumn(columnId)) {
        return 'actions';
    }
    return undefined;
}

function stickyHeadClass(
    columnId: string,
    hasGrandTotal: boolean,
): string | false {
    if (isActionsColumn(columnId)) {
        return cn(STICKY_ACTIONS_HEAD, !hasGrandTotal && STICKY_EDGE_SHADOW);
    }
    if (columnId === 'grand_total' && hasGrandTotal) {
        return STICKY_GRAND_TOTAL_HEAD;
    }

    return false;
}

// Helper function to safely extract placeholder from filter fields
function getPlaceholderFromFilterFields(
    filterFields: FieldDescriptor[],
): string {
    if (filterFields.length === 0) return 'Search...';

    const firstField = filterFields[0];
    if (!firstField) return 'Search...';

    const component = firstField.component;

    if (React.isValidElement(component)) {
        const placeholder = (component.props as { placeholder?: string })
            ?.placeholder;
        return placeholder || 'Search...';
    }

    return 'Search...';
}

type ActionCellOptions<T> = {
    onView?: (item: T) => void;
    onEdit?: (item: T) => void;
    onDelete?: (item: T) => void;
    extraActionItems?: React.ReactNode[];
    viewPath?: (item: T) => string;
};

function createActionsCell<T>(options: ActionCellOptions<T>) {
    const { onView, onEdit, onDelete, extraActionItems, viewPath } = options;

    return ({ row }: { row: { original: T } }) => (
        <GenericActions<T>
            item={row.original}
            onView={onView}
            onEdit={onEdit}
            onDelete={onDelete}
            extraItems={extraActionItems}
            viewUrl={viewPath ? viewPath(row.original) : undefined}
        />
    );
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
    filters?: Record<string, string | number | undefined>;
    onFilterChange: (
        filters: Record<string, string | number | undefined>,
    ) => void;
    onResetFilters: () => void;
    /** Optional endpoint for export requests */
    exportEndpoint?: string;
    edgeFade?: boolean;
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
    /** Ganti pesan kosong default (mis. prompt instruktif filter wajib). */
    emptyState?: React.ReactNode;
    /** Kolom yang disembunyikan secara default (dapat diaktifkan via Columns). */
    defaultColumnVisibility?: Record<string, boolean>;
    /** Urutan awal yang sama dengan default backend; menampilkan indikator sort aktif. */
    defaultSorting?: SortingState;
    controlsDisabled?: boolean;
    /** Baris ringkasan (mis. total) yang tampil tepat di bawah tabel, di atas pagination. */
    footer?: React.ReactNode;
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
    emptyState,
    defaultColumnVisibility,
    defaultSorting,
    controlsDisabled = false,
    footer,
    edgeFade = true,
}: Readonly<DataTableProps<T>>) {
    const [sorting, setSorting] = React.useState<SortingState>(
        defaultSorting ?? [],
    );
    const [columnVisibility, setColumnVisibility] =
        React.useState<VisibilityState>(defaultColumnVisibility ?? {});
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
            Object.entries(filters)
                .filter(([, v]) => v !== undefined && v !== '')
                .map(([key, value]) => [key, String(value)]),
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
        void exportData(
            Object.fromEntries(
                Object.entries(filters).map(([key, value]) => [
                    key,
                    value === undefined ? undefined : String(value),
                ]),
            ),
        );
    }, [exportData, filters]);

    // Sync temporary filters with external filters when they change or when the modal opens
    React.useEffect(() => {
        if (isFilterModalOpen) {
            setTempFilters(
                Object.fromEntries(
                    Object.entries(filters)
                        .filter(([, v]) => v !== undefined && v !== '')
                        .map(([key, value]) => [key, String(value)]),
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
                    cell: createActionsCell<T>({
                        onView,
                        onEdit,
                        onDelete,
                        extraActionItems,
                        viewPath,
                    }),
                };
            }
            return col;
        });
    }, [columns, onEdit, onDelete, onView, extraActionItems]);

    const hasStickyGrandTotal = React.useMemo(() => {
        const columnKey = (column: (typeof columnsWithActions)[number]) => {
            if ('id' in column && column.id) {
                return String(column.id);
            }
            if ('accessorKey' in column && column.accessorKey) {
                return String(column.accessorKey);
            }
            return '';
        };
        const keys = columnsWithActions.map(columnKey);
        const actionsIndex = Math.max(
            keys.lastIndexOf('actions'),
            keys.lastIndexOf('actions_custom'),
        );
        const grandTotalIndex = keys.indexOf('grand_total');

        return actionsIndex > 0 && grandTotalIndex === actionsIndex - 1;
    }, [columnsWithActions]);

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
                    sort_by: nextSorting[0]?.id,
                    sort_direction: nextSorting[0]?.desc ? 'desc' : 'asc',
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
        enableRowSelection: true,
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
        onFilterChange(tempFilters);
        setIsFilterModalOpen(false);
    };

    const loadingRows = [
        'loading-row-1',
        'loading-row-2',
        'loading-row-3',
        'loading-row-4',
        'loading-row-5',
    ];
    const visibleRows = table.getRowModel().rows;

    let tableContent: React.ReactNode;
    if (isLoading) {
        tableContent = loadingRows.map((rowKey) => (
            <TableRow key={rowKey}>
                {columnsWithActions.map((column) => {
                    let columnKey = 'loading-column';
                    if ('id' in column && column.id) {
                        columnKey = String(column.id);
                    } else if ('accessorKey' in column && column.accessorKey) {
                        columnKey = String(column.accessorKey);
                    } else if (typeof column.header === 'string') {
                        columnKey = column.header;
                    }
                    return (
                        <TableCell
                            key={`${rowKey}-${columnKey}`}
                            className={cn(
                                'border-border',
                                stickyCellClass(columnKey, hasStickyGrandTotal),
                            )}
                        >
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
                className="group hover:bg-muted/50"
            >
                {row.getVisibleCells().map((cell, index, cells) => {
                    return (
                        <TableCell
                            key={cell.id}
                            className={cn(
                                'border-border px-2 py-1.5',
                                stickyCellClass(
                                    cell.column.id,
                                    hasStickyGrandTotal,
                                    isActionsColumn(
                                        cells[index + 1]?.column.id ?? '',
                                    ),
                                ),
                            )}
                        >
                            {flexRender(
                                cell.column.columnDef.cell,
                                cell.getContext(),
                            )}
                        </TableCell>
                    );
                })}
            </TableRow>
        ));
    } else {
        tableContent = (
            <TableRow>
                <TableCell
                    colSpan={columnsWithActions.length}
                    className="h-16 py-6 text-center text-sm text-muted-foreground"
                >
                    {emptyState ?? 'No results.'}
                </TableCell>
            </TableRow>
        );
    }

    return (
        <div className="w-full space-y-2 text-foreground">
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
                        Object.entries(filters)
                            .filter(([, v]) => v !== undefined && v !== '')
                            .map(([key, value]) => [key, String(value)]),
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
                exportDisabled={controlsDisabled}
                table={table}
            />

            {Object.keys(rowSelection).length > 0 && (
                <div
                    className="mb-2 flex items-center gap-3 rounded-md border border-border bg-muted/40 px-3 py-2 text-sm"
                    data-testid="bulk-selection-bar"
                    aria-live="polite"
                >
                    <span className="font-medium text-foreground">
                        {Object.keys(rowSelection).length} selected
                    </span>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="h-8 px-2"
                        onClick={() => setRowSelection({})}
                    >
                        Clear selection
                    </Button>
                </div>
            )}

            <div className="overflow-x-auto">
                <Table className="min-w-max" edgeFade={edgeFade}>
                    <TableHeader className="[&_th]:text-muted-foreground">
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map(
                                    (header, index, headers) => {
                                        return (
                                            <TableHead
                                                key={header.id}
                                                data-sticky-column={stickyColumnAttr(
                                                    header.column.id,
                                                    hasStickyGrandTotal,
                                                )}
                                                className={cn(
                                                    'h-9 border-border px-2 py-1.5 text-sm font-medium select-none',
                                                    stickyHeadClass(
                                                        header.column.id,
                                                        hasStickyGrandTotal,
                                                    ),
                                                    isActionsColumn(
                                                        headers[index + 1]
                                                            ?.column.id ?? '',
                                                    ) &&
                                                        CELL_BEFORE_STICKY_ACTIONS,
                                                )}
                                            >
                                                {header.isPlaceholder
                                                    ? null
                                                    : flexRender(
                                                          header.column
                                                              .columnDef.header,
                                                          header.getContext(),
                                                      )}
                                            </TableHead>
                                        );
                                    },
                                )}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>{tableContent}</TableBody>
                </Table>
            </div>

            {footer ? <div className="mt-3">{footer}</div> : null}

            <div className="border-t border-border/50 pt-1">
                <DataTablePagination
                    pagination={pagination}
                    onPageChange={handlePageChange}
                    onPageSizeChange={(per_page: number) =>
                        handlePageSizeChange(per_page.toString())
                    }
                    disabled={controlsDisabled}
                />
            </div>
        </div>
    );
}
