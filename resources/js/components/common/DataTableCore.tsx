'use client';

import { GenericActions } from '@/components/common/ActionsDropdown';
import { DataTablePagination } from '@/components/common/DataTablePagination';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    ColumnDef,
    SortingState,
    VisibilityState,
    flexRender,
    getCoreRowModel,
    getSortedRowModel,
    useReactTable,
} from '@tanstack/react-table';
import {
    ChevronDown,
    Download,
    Filter,
    Loader2,
    PlusCircle,
} from 'lucide-react';
import * as React from 'react';
import { useExport } from '@/hooks/useExport';
import type { FieldDescriptor } from './filters';

// FilterModal component to reduce complexity in DataTable
interface FilterModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    filterFields: FieldDescriptor[];
    tempFilters: Record<string, string>;
    onTempFiltersChange: (filters: Record<string, string>) => void;
    onApply: () => void;
    onReset: () => void;
    onClearAll: () => void;
}

function FilterModal({
    open,
    onOpenChange,
    filterFields,
    tempFilters,
    onTempFiltersChange,
    onApply,
    onReset,
    onClearAll,
}: FilterModalProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="border-border bg-background text-foreground sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Filters</DialogTitle>
                    <DialogDescription className="text-muted-foreground">
                        Apply filters to refine the results
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-4 py-4">
                    {filterFields.map((field) => {
                        const element = React.isValidElement(field.component) ? field.component : null;
                        const isSelect = element && (element.type === Select || (element.type as { displayName?: string })?.displayName === 'Select');

                        const commonProps = {
                            value: tempFilters[field.name] ?? '',
                            placeholder: (element?.props as { placeholder?: string })?.placeholder ?? '',
                        };

                        const onChangeHandler = (e: React.ChangeEvent<HTMLInputElement>) => {
                            onTempFiltersChange({ ...tempFilters, [field.name]: e.target.value });
                        };

                        const onValueChangeHandler = (value: string) => {
                            onTempFiltersChange({ ...tempFilters, [field.name]: value });
                        };

                        const componentWithProps = element
                            ? React.cloneElement(element, {
                                  ...commonProps,
                                  ...(isSelect ? { onValueChange: onValueChangeHandler } : { onChange: onChangeHandler }),
                              })
                            : null;

                        return (
                            <div key={field.name}>
                                <label className="mb-2 block text-sm font-medium">{field.label}</label>
                                {componentWithProps}
                            </div>
                        );
                    })}
                </div>
                <DialogFooter>
                    <Button variant="outline" onClick={onReset}>
                        Reset
                    </Button>
                    <Button variant="outline" onClick={onClearAll}>
                        Clear All
                    </Button>
                    <Button onClick={onApply}>Apply Filters</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

// Helper function to safely extract placeholder from filter fields
function getPlaceholderFromFilterFields(filterFields: FieldDescriptor[]): string {
    if (filterFields.length === 0) return 'Search...';

    const firstField = filterFields[0];
    const component = firstField.component;

    if (React.isValidElement(component)) {
        const placeholder = (component.props as { placeholder?: string })?.placeholder;
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
    entityName,
}: DataTableProps<T>) {
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
            return [{
                name: 'search',
                label: 'Search',
                component: <Input placeholder={`Search ${entityName.toLowerCase()}s...`} />,
            }];
        }
        return filterFields;
    }, [filterFields, entityName]);

    // Memoize sorting change handler
    const handleSortingChange = React.useCallback((columnId: string) => {
        const existing = sorting.find((s) => s.id === columnId);
        const newSorting: SortingState = existing
            ? [{ id: columnId, desc: !existing.desc }]
            : [{ id: columnId, desc: false }];

        setSorting(newSorting);
        onFilterChange({
            ...filters,
            sort_by: columnId,
            sort_direction: newSorting[0].desc ? 'desc' : 'asc',
        });
    }, [sorting, filters, onFilterChange]);

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
                return {
                    ...col,
                    cell: ({ row }: { row: { original: T } }) => (
                        <GenericActions<T>
                            item={row.original}
                            onView={onView}
                            onEdit={onEdit!}
                            onDelete={onDelete!}
                            extraItems={extraActionItems}
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
        onSortingChange: setSorting,
        onColumnVisibilityChange: setColumnVisibility,
        onRowSelectionChange: setRowSelection,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
        manualPagination: true,
    });

    const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSearchValue(e.target.value);
    };

    const handleSearchKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            onSearchChange(searchValue);
        }
    };

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

    return (
        <div className="w-full bg-background text-foreground">
            {/* Toolbar */}
            <div className="items-center justify-between py-4 lg:flex">
                <div className="mb-2 flex items-center space-x-2">
                    <Input
                        placeholder={
                            defaultFilterFields && defaultFilterFields.length > 0
                                ? getPlaceholderFromFilterFields(defaultFilterFields)
                                : 'Search...'
                        }
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
                        filterFields={defaultFilterFields}
                        tempFilters={tempFilters}
                        onTempFiltersChange={setTempFilters}
                        onApply={handleApplyFilters}
                        onReset={() => {
                            const cleaned = Object.fromEntries(
                                Object.entries(filters).filter(([_, v]) => v !== undefined && v !== '')
                            ) as Record<string, string>;
                            setTempFilters(cleaned);
                        }}
                        onClearAll={() => {
                            onResetFilters();
                            setTempFilters({});
                            setIsFilterModalOpen(false);
                        }}
                    />
                    <Button size="sm" onClick={onAdd ? onAdd : undefined}>
                        <PlusCircle className="mr-2 h-4 w-4" />
                        Add
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={handleExport}
                        disabled={data.length === 0 || exporting}
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

            {/* Table */}
            <div className="overflow-hidden rounded-md border border-border">
                <Table>
                    <TableHeader className="bg-muted">
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map((header) => (
                                    <TableHead
                                        key={header.id}
                                        className="cursor-pointer border-border select-none"
                                        onClick={() =>
                                            handleSortingChange(
                                                header.column.id,
                                            )
                                        }
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
                    <TableBody>
                        {isLoading ? (
                            Array.from({ length: 5 }).map((_, idx) => (
                                <TableRow key={idx}>
                                    {Array.from({ length: columns.length }).map(
                                        (_, cellIdx) => (
                                            <TableCell
                                                key={cellIdx}
                                                className="border-border"
                                            >
                                                <Skeleton className="h-4 w-full bg-muted" />
                                            </TableCell>
                                        ),
                                    )}
                                </TableRow>
                            ))
                        ) : table.getRowModel().rows.length ? (
                            table.getRowModel().rows.map((row) => (
                                <TableRow
                                    key={row.id}
                                    data-state={
                                        row.getIsSelected() && 'selected'
                                    }
                                    className="hover:bg-muted/50"
                                >
                                    {row.getVisibleCells().map((cell) => (
                                        <TableCell
                                            key={cell.id}
                                            className="border-border"
                                        >
                                            {flexRender(
                                                cell.column.columnDef.cell,
                                                cell.getContext(),
                                            )}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={columns.length}
                                    className="h-24 text-center text-muted-foreground"
                                >
                                    No results.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            {/* Pagination */}
            <DataTablePagination
                pagination={pagination}
                onPageChange={handlePageChange}
                onPageSizeChange={(per_page: number) => handlePageSizeChange(per_page.toString())}
            />
        </div>
    );
}
