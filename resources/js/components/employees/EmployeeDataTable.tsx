'use client';

import {
    SortingState,
    VisibilityState,
    flexRender,
    getCoreRowModel,
    getSortedRowModel,
    useReactTable,
} from '@tanstack/react-table';
import axios from 'axios';
import { ChevronDown, Download, Filter, PlusCircle } from 'lucide-react';
import * as React from 'react';

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
import { Employee } from '@/types';
import { employeeColumns } from './EmployeeColumns';

interface EmployeeDataTableProps {
    data: Employee[];
    onAddEmployee: () => void;
    onEditEmployee: (employee: Employee) => void;
    onDeleteEmployee: (employee: Employee) => void;
    onViewEmployee: (employee: Employee) => void;
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
    filters?: {
        search?: string;
        department?: string;
        position?: string;
        min_salary?: string;
        max_salary?: string;
        hire_date_from?: string;
        hire_date_to?: string;
        sort_by?: string;
        sort_direction?: string;
    };
    onFilterChange: (filters: Record<string, string | undefined>) => void;
    onResetFilters: () => void;
}

export function EmployeeDataTable({
    data,
    onAddEmployee,
    onEditEmployee,
    onDeleteEmployee,
    onViewEmployee,
    pagination,
    onPageChange,
    onPageSizeChange,
    onSearchChange,
    isLoading,
    filterValue = '',
    filters,
    onFilterChange,
    onResetFilters,
}: EmployeeDataTableProps) {
    const [sorting, setSorting] = React.useState<SortingState>([]);
    const [columnVisibility, setColumnVisibility] =
        React.useState<VisibilityState>({});
    const [rowSelection, setRowSelection] = React.useState({});
    const [searchValue, setSearchValue] = React.useState(filterValue);
    const [isFilterModalOpen, setIsFilterModalOpen] = React.useState(false);

    // Temporary filter states for modal
    const [tempFilters, setTempFilters] = React.useState({
        search: filters?.search || '',
        department: filters?.department || '',
        position: filters?.position || '',
    });

    // Create columns with action handlers
    const columnsWithActions = React.useMemo(() => {
        return employeeColumns.map((column) => {
            if (column.id === 'actions') {
                return {
                    ...column,
                    cell: ({ row }: { row: { original: Employee } }) => {
                        const employee = row.original;
                        return (
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="ghost" size="sm">
                                        Actions <ChevronDown className="ml-2 h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuCheckboxItem
                                        key="view"
                                        className="capitalize"
                                        checked={false}
                                        onCheckedChange={() => onViewEmployee(employee)}
                                    >
                                        View
                                    </DropdownMenuCheckboxItem>
                                    <DropdownMenuCheckboxItem
                                        key="edit"
                                        className="capitalize"
                                        checked={false}
                                        onCheckedChange={() => onEditEmployee(employee)}
                                    >
                                        Edit
                                    </DropdownMenuCheckboxItem>
                                    <DropdownMenuCheckboxItem
                                        key="delete"
                                        className="capitalize text-destructive"
                                        checked={false}
                                        onCheckedChange={() => onDeleteEmployee(employee)}
                                    >
                                        Delete
                                    </DropdownMenuCheckboxItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        );
                    },
                };
            }
            return column;
        });
    }, [onEditEmployee, onDeleteEmployee, onViewEmployee]);

    const table = useReactTable({
        data,
        columns: columnsWithActions,
        onSortingChange: setSorting,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
        onColumnVisibilityChange: setColumnVisibility,
        onRowSelectionChange: setRowSelection,
        state: {
            sorting,
            columnVisibility,
            rowSelection,
        },
        manualPagination: true,
    });

    const handleSearchChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const value = event.target.value;
        setSearchValue(value);
    };

    const handleSearchKeyDown = (
        event: React.KeyboardEvent<HTMLInputElement>,
    ) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            onSearchChange(searchValue);
        }
    };

    // Update search when filterValue prop changes
    React.useEffect(() => {
        setSearchValue(filterValue);
    }, [filterValue]);

    const handlePageChange = (page: number) => {
        onPageChange(page);
    };

    const handlePageSizeChange = (per_page: string) => {
        onPageSizeChange(Number(per_page));
    };

    const renderPageNumbers = () => {
        const pages = [];
        const maxPages = 5;
        const startPage = Math.max(
            1,
            pagination.page - Math.floor(maxPages / 2),
        );
        const endPage = Math.min(
            pagination.last_page,
            startPage + maxPages - 1,
        );

        for (let i = startPage; i <= endPage; i++) {
            pages.push(
                <PaginationItem key={i}>
                    <PaginationLink
                        href="#"
                        isActive={i === pagination.page}
                        onClick={(e: React.MouseEvent<HTMLAnchorElement>) => {
                            e.preventDefault();
                            handlePageChange(i);
                        }}
                    >
                        {i}
                    </PaginationLink>
                </PaginationItem>,
            );
        }
        return pages;
    };

    const handleExport = async () => {
        try {
            // Use filters from props
            const cleanFilters = Object.fromEntries(
                Object.entries(filters || {}).filter(
                    ([, value]) => value !== null && value !== '',
                ),
            );

            const response = await axios.post(
                '/api/employees/export',
                cleanFilters,
                {
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                },
            );

            // Create download link and trigger download
            const a = document.createElement('a');
            a.href = response.data.url;
            a.download = response.data.filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        } catch (error) {
            console.error('Export error:', error);
            alert('Failed to export employees. Please try again.');
        }
    };

    const handleApplyFilters = () => {
        onFilterChange(tempFilters);
        setIsFilterModalOpen(false);
    };

    const handleResetModalFilters = () => {
        setTempFilters({
            search: '',
            department: '',
            position: '',
        });
    };

    const handleResetFiltersFromModal = () => {
        onResetFilters();
        setIsFilterModalOpen(false);
    };

    return (
        <div className="w-full">
            <div className="lg:flex items-center justify-between py-4">
              <div className="flex items-center space-x-2 mb-2">
                <Input
                    placeholder="Search employees..."
                    value={searchValue}
                    onChange={handleSearchChange}
                    onKeyDown={handleSearchKeyDown}
                    className="max-w-sm"
                />
              </div>
              <div className="flex items-center space-x-2 mb-2">
                <Dialog
                  open={isFilterModalOpen}
                  onOpenChange={setIsFilterModalOpen}
                >
                  <DialogTrigger asChild>
                    <Button variant="outline" size="sm">
                      <Filter className="mr-2 h-4 w-4" />
                      Filters
                    </Button>
                  </DialogTrigger>
                  <DialogContent className="sm:max-w-[425px]">
                    <DialogHeader>
                      <DialogTitle>Filter Employees</DialogTitle>
                      <DialogDescription>
                        Apply filters to find specific employees
                      </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4 py-4">
                      <div>
                        <label className="mb-2 block text-sm font-medium">
                          Search
                        </label>
                        <Input
                          placeholder="Search employees..."
                          value={tempFilters.search}
                          onChange={(e) =>
                            setTempFilters((prev) => ({
                              ...prev,
                              search: e.target.value,
                            }))
                          }
                        />
                      </div>
                      <div>
                        <label className="mb-2 block text-sm font-medium">
                          Department
                        </label>
                        <Select
                          value={tempFilters.department}
                          onValueChange={(value) =>
                            setTempFilters((prev) => ({
                              ...prev,
                              department: value,
                            }))
                          }
                        >
                          <SelectTrigger>
                            <SelectValue placeholder="All departments" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="all-departments">
                              All departments
                            </SelectItem>
                            <SelectItem value="Engineering">
                              Engineering
                            </SelectItem>
                            <SelectItem value="Marketing">
                              Marketing
                            </SelectItem>
                            <SelectItem value="Sales">
                              Sales
                            </SelectItem>
                            <SelectItem value="HR">
                              HR
                            </SelectItem>
                            <SelectItem value="Finance">
                              Finance
                            </SelectItem>
                            <SelectItem value="Operations">
                              Operations
                            </SelectItem>
                          </SelectContent>
                        </Select>
                      </div>
                      <div>
                        <label className="mb-2 block text-sm font-medium">
                          Position
                        </label>
                        <Select
                          value={tempFilters.position}
                          onValueChange={(value) =>
                            setTempFilters((prev) => ({
                              ...prev,
                              position: value,
                            }))
                          }
                        >
                          <SelectTrigger>
                            <SelectValue placeholder="All positions" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="all-positions">
                              All positions
                            </SelectItem>
                            <SelectItem value="Manager">
                              Manager
                            </SelectItem>
                            <SelectItem value="Senior Developer">
                              Senior Developer
                            </SelectItem>
                            <SelectItem value="Developer">
                              Developer
                            </SelectItem>
                            <SelectItem value="Junior Developer">
                              Junior Developer
                            </SelectItem>
                            <SelectItem value="Designer">
                              Designer
                            </SelectItem>
                            <SelectItem value="Analyst">
                              Analyst
                            </SelectItem>
                            <SelectItem value="Coordinator">
                              Coordinator
                            </SelectItem>
                          </SelectContent>
                        </Select>
                      </div>
                    </div>
                    <DialogFooter>
                      <Button
                        variant="outline"
                        onClick={handleResetModalFilters}
                      >
                        Reset
                      </Button>
                      <Button
                        variant="outline"
                        onClick={handleResetFiltersFromModal}
                      >
                        Clear All
                      </Button>
                      <Button onClick={handleApplyFilters}>
                        Apply Filters
                      </Button>
                    </DialogFooter>
                  </DialogContent>
                </Dialog>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={handleExport}
                  disabled={data.length === 0}
                >
                  <Download className="mr-2 h-4 w-4" />
                  Export
                </Button>
                <DropdownMenu>
                  <DropdownMenuTrigger asChild>
                    <Button variant="outline" size="sm">
                      Columns <ChevronDown className="ml-2 h-4 w-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    {table
                      .getAllColumns()
                      .filter((column) => column.getCanHide())
                      .map((column) => {
                        return (
                          <DropdownMenuCheckboxItem
                            key={column.id}
                            className="capitalize"
                            checked={column.getIsVisible()}
                            onCheckedChange={(value) =>
                              column.toggleVisibility(!!value)
                            }
                          >
                            {column.id}
                          </DropdownMenuCheckboxItem>
                        );
                      })}
                  </DropdownMenuContent>
                </DropdownMenu>
                <Button size="sm" onClick={onAddEmployee}>
                  <PlusCircle className="mr-2 h-4 w-4" />
                  Add
                </Button>
              </div>
            </div>
            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map((header) => {
                                    return (
                                        <TableHead key={header.id}>
                                            {header.isPlaceholder
                                                ? null
                                                : flexRender(
                                                      header.column.columnDef
                                                          .header,
                                                      header.getContext(),
                                                  )}
                                        </TableHead>
                                    );
                                })}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {isLoading ? (
                            Array.from({ length: 5 }).map((_, index) => (
                                <TableRow key={index}>
                                    {Array.from({
                                        length: employeeColumns.length,
                                    }).map((_, cellIndex) => (
                                        <TableCell key={cellIndex}>
                                            <Skeleton className="h-4 w-full" />
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        ) : table.getRowModel().rows?.length ? (
                            table.getRowModel().rows.map((row) => (
                                <TableRow
                                    key={row.id}
                                    data-state={
                                        row.getIsSelected() && 'selected'
                                    }
                                >
                                    {row.getVisibleCells().map((cell) => (
                                        <TableCell key={cell.id}>
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
                                    colSpan={employeeColumns.length}
                                    className="h-24 text-center"
                                >
                                    No results.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            {/* Pagination Controls */}
            <div className="flex items-center justify-between py-4">
                <div className="flex items-center space-x-2">
                    <p className="text-sm text-muted-foreground">
                        Rows per page
                    </p>
                    <Select
                        value={String(pagination.per_page)}
                        onValueChange={handlePageSizeChange}
                    >
                        <SelectTrigger className="w-[70px]">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="10">10</SelectItem>
                            <SelectItem value="15">15</SelectItem>
                            <SelectItem value="25">25</SelectItem>
                            <SelectItem value="50">50</SelectItem>
                            <SelectItem value="100">100</SelectItem>
                        </SelectContent>
                    </Select>
                    <p className="text-sm text-muted-foreground">
                        Showing {pagination.from} to {pagination.to} of{' '}
                        {pagination.total} entries
                    </p>
                </div>

                <Pagination>
                    <PaginationContent>
                        <PaginationItem>
                            <PaginationPrevious
                                href="#"
                                onClick={(
                                    e: React.MouseEvent<HTMLAnchorElement>,
                                ) => {
                                    e.preventDefault();
                                    if (pagination.page > 1) {
                                        handlePageChange(pagination.page - 1);
                                    }
                                }}
                                aria-disabled={pagination.page <= 1}
                                className={
                                    pagination.page <= 1
                                        ? 'pointer-events-none opacity-50'
                                        : ''
                                }
                            />
                        </PaginationItem>

                        {renderPageNumbers()}

                        <PaginationItem>
                            <PaginationNext
                                href="#"
                                onClick={(
                                    e: React.MouseEvent<HTMLAnchorElement>,
                                ) => {
                                    e.preventDefault();
                                    if (
                                        pagination.page < pagination.last_page
                                    ) {
                                        handlePageChange(pagination.page + 1);
                                    }
                                }}
                                aria-disabled={
                                    pagination.page >= pagination.last_page
                                }
                                className={
                                    pagination.page >= pagination.last_page
                                        ? 'pointer-events-none opacity-50'
                                        : ''
                                }
                            />
                        </PaginationItem>
                    </PaginationContent>
                </Pagination>
            </div>
        </div>
    );
}
