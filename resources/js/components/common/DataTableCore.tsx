'use client';

import * as React from 'react';
import {
    SortingState,
    VisibilityState,
    flexRender,
    getCoreRowModel,
    getSortedRowModel,
    useReactTable,
} from '@tanstack/react-table';
import axios from 'axios';
import { Loader2, Download, Filter, PlusCircle, ChevronDown } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { GenericActions } from '@/components/common/ActionsDropdown';
import { ColumnDef } from '@tanstack/react-table';

type FieldDescriptor = {
    name: string;
    label: string;
    component: React.ReactNode;
};

interface GenericDataTableProps<T> {
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
}

/**
 * GenericDataTable – a reusable data‑table component.
 *
 * It mirrors the behaviour of the existing Employee/Position/Department tables
 * while exposing a flexible API via props.
 *
 * Props mapping:
 * - `columns` – column definitions (including any custom renderers)
 * - `data` – row data
 * - Pagination & search props – identical to the original tables
 * - `filterFields` – array of field descriptors rendered by the built‑in filter modal
 * - `onAdd`, `onEdit`, `onDelete`, `onView` – callbacks wired to the generic
 *   {@link GenericActions} dropdown.
 * - `exportEndpoint` – URL used for the export request (defaults to `/api/export`)
 */
export function GenericDataTable<T>({
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
}: GenericDataTableProps<T>) {
    const [sorting, setSorting] = React.useState<SortingState>([]);
    const [columnVisibility, setColumnVisibility] = React.useState<VisibilityState>({});
    const [rowSelection, setRowSelection] = React.useState({});
    const [searchValue, setSearchValue] = React.useState(filterValue);
    const [isFilterModalOpen, setIsFilterModalOpen] = React.useState(false);
    const [exporting, setExporting] = React.useState(false);

    // Temporary filter state for the modal
    const [tempFilters, setTempFilters] = React.useState<Record<string, string>>(
        Object.fromEntries(
            Object.entries(filters).filter(([, v]) => v !== undefined && v !== '')
        ) as Record<string, string>
    );

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

    const handleSortingChange = (columnId: string) => {
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
    };

    const renderPageNumbers = () => {
        const pages = [];
        const maxPages = 5;
        const startPage = Math.max(1, pagination.page - Math.floor(maxPages / 2));
        const endPage = Math.min(pagination.last_page, startPage + maxPages - 1);

        for (let i = startPage; i <= endPage; i++) {
            pages.push(
                <PaginationItem key={i}>
                    <PaginationLink
                        href="#"
                        isActive={i === pagination.page}
                        onClick={(e) => {
                            e.preventDefault();
                            handlePageChange(i);
                        }}
                    >
                        {i}
                    </PaginationLink>
                </PaginationItem>
            );
        }
        return pages;
    };

    const handleExport = async () => {
        if (!exportEndpoint) return;
        setExporting(true);
        try {
            const cleanFilters = Object.fromEntries(
                Object.entries(filters).filter(([, v]) => v !== null && v !== '')
            );
            const response = await axios.post(exportEndpoint, cleanFilters, {
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
            });
            const a = document.createElement('a');
            a.href = response.data.url;
            a.download = response.data.filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        } catch {
            alert('Failed to export data. Please try again.');
        } finally {
            setExporting(false);
        }
    };

    const handleApplyFilters = () => {
        onFilterChange(tempFilters);
        setIsFilterModalOpen(false);
    };

    const handleResetModalFilters = () => {
        setTempFilters({});
    };

    const handleResetFiltersFromModal = () => {
        onResetFilters();
        setIsFilterModalOpen(false);
    };

    return (
        <div className="w-full bg-background text-foreground">
            {/* Toolbar */}
            <div className="items-center justify-between py-4 lg:flex">
                <div className="mb-2 flex items-center space-x-2">
                    <Input
                        placeholder="Search..."
                        value={searchValue}
                        onChange={handleSearchChange}
                        onKeyDown={handleSearchKeyDown}
                        className="max-w-sm border-border bg-background placeholder:text-muted-foreground"
                    />
                </div>
                <div className="mb-2 flex items-center space-x-2">
                    {filterFields.length > 0 && (
                        <Dialog open={isFilterModalOpen} onOpenChange={setIsFilterModalOpen}>
                            <DialogTrigger asChild>
                                <Button variant="outline" size="sm">
                                    <Filter className="mr-2 h-4 w-4" />
                                    Filters
                                </Button>
                            </DialogTrigger>
                            <DialogContent className="border-border bg-background text-foreground sm:max-w-[425px]">
                                <DialogHeader>
                                    <DialogTitle>Filters</DialogTitle>
                                    <DialogDescription className="text-muted-foreground">
                                        Apply filters to refine the results
                                    </DialogDescription>
                                </DialogHeader>
                                <div className="grid gap-4 py-4">
                                    {filterFields.map((field) => (
                                        <div key={field.name}>
                                            <label className="mb-2 block text-sm font-medium">
                                                {field.label}
                                            </label>
                                            {field.component}
                                        </div>
                                    ))}
                                </div>
                                <DialogFooter>
                                    <Button variant="outline" onClick={handleResetModalFilters}>
                                        Reset
                                    </Button>
                                    <Button variant="outline" onClick={handleResetFiltersFromModal}>
                                        Clear All
                                    </Button>
                                    <Button onClick={handleApplyFilters}>Apply</Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    )}
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
                                        onCheckedChange={(value) => col.toggleVisibility(!!value)}
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
                                        onClick={() => handleSortingChange(header.column.id)}
                                    >
                                        {header.isPlaceholder
                                            ? null
                                            : flexRender(
                                                  header.column.columnDef.header,
                                                  header.getContext()
                                              )}
                                    </TableHead>
                                ))}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {isLoading
                            ? Array.from({ length: 5 }).map((_, idx) => (
                                  <TableRow key={idx}>
                                      {Array.from({ length: columns.length }).map((_, cellIdx) => (
                                          <TableCell key={cellIdx} className="border-border">
                                              <Skeleton className="h-4 w-full bg-muted" />
                                          </TableCell>
                                      ))}
                                  </TableRow>
                              ))
                            : table.getRowModel().rows.length
                            ? table.getRowModel().rows.map((row) => (
                                  <TableRow
                                      key={row.id}
                                      data-state={row.getIsSelected() && 'selected'}
                                      className="hover:bg-muted/50"
                                  >
                                      {row.getVisibleCells().map((cell) => (
                                          <TableCell key={cell.id} className="border-border">
                                              {flexRender(
                                                  cell.column.columnDef.cell,
                                                  cell.getContext()
                                              )}
                                          </TableCell>
                                      ))}
                                  </TableRow>
                              ))
                            : (
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
            <div className="flex items-center justify-between py-4 text-sm text-muted-foreground">
                <div className="flex items-center space-x-2">
                    <p>Rows per page</p>
                    <Select
                        value={String(pagination.per_page)}
                        onValueChange={handlePageSizeChange}
                    >
                        <SelectTrigger className="w-[70px] border-border bg-background">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent className="border-border bg-background text-foreground">
                            <SelectItem value="10">10</SelectItem>
                            <SelectItem value="15">15</SelectItem>
                            <SelectItem value="25">25</SelectItem>
                            <SelectItem value="50">50</SelectItem>
                            <SelectItem value="100">100</SelectItem>
                        </SelectContent>
                    </Select>
                    <p>
                        Showing {pagination.from} to {pagination.to} of {pagination.total}{' '}
                        entries
                    </p>
                </div>

                <Pagination>
                    <PaginationContent>
                        <PaginationItem>
                            <PaginationPrevious
                                href="#"
                                onClick={(e) => {
                                    e.preventDefault();
                                    if (pagination.page > 1) {
                                        handlePageChange(pagination.page - 1);
                                    }
                                }}
                                aria-disabled={pagination.page <= 1}
                                className={pagination.page <= 1 ? 'pointer-events-none opacity-50' : ''}
                            />
                        </PaginationItem>

                        {renderPageNumbers()}

                        <PaginationItem>
                            <PaginationNext
                                href="#"
                                onClick={(e) => {
                                    e.preventDefault();
                                    if (pagination.page < pagination.last_page) {
                                        handlePageChange(pagination.page + 1);
                                    }
                                }}
                                aria-disabled={pagination.page >= pagination.last_page}
                                className={pagination.page >= pagination.last_page ? 'pointer-events-none opacity-50' : ''}
                            />
                        </PaginationItem>
                    </PaginationContent>
                </Pagination>
            </div>
        </div>
    );
}
