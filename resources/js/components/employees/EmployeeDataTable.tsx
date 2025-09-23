"use client"

import * as React from "react"
import axios from 'axios'
import {
  ColumnDef,
  ColumnFiltersState,
  SortingState,
  VisibilityState,
  flexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
} from "@tanstack/react-table"
import { ChevronDown, Download, PlusCircle } from "lucide-react"

import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { Input } from "@/components/ui/input"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationLink,
  PaginationNext,
  PaginationPrevious,
} from "@/components/ui/pagination"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { Skeleton } from "@/components/ui/skeleton"
import { Employee } from "@/types"
import { employeeColumns } from "./EmployeeColumns"

interface EmployeeDataTableProps {
  data: Employee[]
  onAddEmployee: () => void
  onEditEmployee: (employee: Employee) => void
  onDeleteEmployee: (employee: Employee) => void
  onViewEmployee: (employee: Employee) => void
  pagination: {
    page: number
    per_page: number
    total: number
    last_page: number
    from: number
    to: number
  }
  onPageChange: (page: number) => void
  onPageSizeChange: (per_page: number) => void
  onSearchChange: (search: string) => void
  isLoading?: boolean
  filterValue?: string
  filters?: {
    search?: string
    department?: string
    position?: string
    min_salary?: string
    max_salary?: string
    hire_date_from?: string
    hire_date_to?: string
    sort_by?: string
    sort_direction?: string
  }
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
  filterValue = "",
  filters,
}: EmployeeDataTableProps) {
  const [sorting, setSorting] = React.useState<SortingState>([])
  const [columnVisibility, setColumnVisibility] = React.useState<VisibilityState>({})
  const [rowSelection, setRowSelection] = React.useState({})
  const [searchValue, setSearchValue] = React.useState(filterValue)

  // Create columns with action handlers
  const columnsWithActions = React.useMemo(() => {
    return employeeColumns.map(column => {
      if (column.id === "actions") {
        return {
          ...column,
          cell: ({ row }: { row: { original: Employee } }) => {
            const employee = row.original
            return (
              <div className="flex items-center space-x-2">
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => onViewEmployee(employee)}
                >
                  View
                </Button>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => onEditEmployee(employee)}
                >
                  Edit
                </Button>
                <Button
                  variant="ghost"
                  size="sm"
                  className="text-destructive hover:text-destructive"
                  onClick={() => onDeleteEmployee(employee)}
                >
                  Delete
                </Button>
              </div>
            )
          },
        }
      }
      return column
    })
  }, [onEditEmployee, onDeleteEmployee, onViewEmployee])

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
  })

  const handleSearchChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const value = event.target.value
    setSearchValue(value)
  }

  const handleSearchKeyDown = (event: React.KeyboardEvent<HTMLInputElement>) => {
    if (event.key === 'Enter') {
      event.preventDefault()
      onSearchChange(searchValue)
    }
  }

  // Update search when filterValue prop changes
  React.useEffect(() => {
    setSearchValue(filterValue)
  }, [filterValue])

  const handlePageChange = (page: number) => {
    onPageChange(page)
  }

  const handlePageSizeChange = (per_page: string) => {
    onPageSizeChange(Number(per_page))
  }

  const renderPageNumbers = () => {
    const pages = []
    const maxPages = 5
    const startPage = Math.max(1, pagination.page - Math.floor(maxPages / 2))
    const endPage = Math.min(pagination.last_page, startPage + maxPages - 1)

    for (let i = startPage; i <= endPage; i++) {
      pages.push(
        <PaginationItem key={i}>
          <PaginationLink
            href="#"
            isActive={i === pagination.page}
            onClick={(e: React.MouseEvent<HTMLAnchorElement>) => {
              e.preventDefault()
              handlePageChange(i)
            }}
          >
            {i}
          </PaginationLink>
        </PaginationItem>
      )
    }
    return pages
  }

  const handleExport = async () => {
    try {
      // Use filters from props
      const cleanFilters = Object.fromEntries(
        Object.entries(filters || {}).filter(([_, value]) => value !== null && value !== '')
      )

      const response = await axios.post('/api/employees/export', cleanFilters, {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        }
      })

      // Create download link and trigger download
      const a = document.createElement('a')
      a.href = response.data.url
      a.download = response.data.filename
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)

    } catch (error) {
      console.error('Export error:', error)
      alert('Failed to export employees. Please try again.')
    }
  }

  return (
    <div className="w-full">
      <div className="flex items-center justify-between py-4">
        <Input
          placeholder="Search employees..."
          value={searchValue}
          onChange={handleSearchChange}
          onKeyDown={handleSearchKeyDown}
          className="max-w-sm"
        />
        <div className="flex items-center space-x-2">
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
                  )
                })}
            </DropdownMenuContent>
          </DropdownMenu>
          <Button size="sm" onClick={onAddEmployee}>
            <PlusCircle className="mr-2 h-4 w-4" />
            Add Employee
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
                            header.column.columnDef.header,
                            header.getContext()
                          )}
                    </TableHead>
                  )
                })}
              </TableRow>
            ))}
          </TableHeader>
          <TableBody>
            {isLoading ? (
              Array.from({ length: 5 }).map((_, index) => (
                <TableRow key={index}>
                  {Array.from({ length: employeeColumns.length }).map((_, cellIndex) => (
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
                  data-state={row.getIsSelected() && "selected"}
                >
                  {row.getVisibleCells().map((cell) => (
                    <TableCell key={cell.id}>
                      {flexRender(cell.column.columnDef.cell, cell.getContext())}
                    </TableCell>
                  ))}
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell colSpan={employeeColumns.length} className="h-24 text-center">
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
          <p className="text-sm text-muted-foreground">Rows per page</p>
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
          <p className="text-sm text-muted-foreground">Showing {pagination.from} to {pagination.to} of {pagination.total} entries</p>
        </div>

        <Pagination>
          <PaginationContent>
            <PaginationItem>
              <PaginationPrevious
                href="#"
                onClick={(e: React.MouseEvent<HTMLAnchorElement>) => {
                  e.preventDefault()
                  if (pagination.page > 1) {
                    handlePageChange(pagination.page - 1)
                  }
                }}
                aria-disabled={pagination.page <= 1}
                className={pagination.page <= 1 ? "pointer-events-none opacity-50" : ""}
              />
            </PaginationItem>

            {renderPageNumbers()}

            <PaginationItem>
              <PaginationNext
                href="#"
                onClick={(e: React.MouseEvent<HTMLAnchorElement>) => {
                  e.preventDefault()
                  if (pagination.page < pagination.last_page) {
                    handlePageChange(pagination.page + 1)
                  }
                }}
                aria-disabled={pagination.page >= pagination.last_page}
                className={pagination.page >= pagination.last_page ? "pointer-events-none opacity-50" : ""}
              />
            </PaginationItem>
          </PaginationContent>
        </Pagination>
      </div>
    </div>
  )
}
