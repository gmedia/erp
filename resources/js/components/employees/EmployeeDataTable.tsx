"use client"

import * as React from "react"
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
  isLoading?: boolean
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
  isLoading,
}: EmployeeDataTableProps) {
  const [sorting, setSorting] = React.useState<SortingState>([])
  const [columnFilters, setColumnFilters] = React.useState<ColumnFiltersState>([])
  const [columnVisibility, setColumnVisibility] = React.useState<VisibilityState>({})
  const [rowSelection, setRowSelection] = React.useState({})

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
    onColumnFiltersChange: setColumnFilters,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    onColumnVisibilityChange: setColumnVisibility,
    onRowSelectionChange: setRowSelection,
    state: {
      sorting,
      columnFilters,
      columnVisibility,
      rowSelection,
    },
    manualPagination: true,
  })

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

  const handleExport = () => {
    const selectedRows = table.getFilteredSelectedRowModel().rows
    const dataToExport = selectedRows.length > 0 ? selectedRows.map(row => row.original) : data
    
    const csvContent = [
      ["Name", "Email", "Phone", "Department", "Position", "Salary", "Hire Date"],
      ...dataToExport.map(employee => [
        employee.name,
        employee.email,
        employee.phone,
        employee.department,
        employee.position,
        employee.salary ? parseFloat(employee.salary).toFixed(2) : "0.00",
        employee.hire_date ? new Date(employee.hire_date).toLocaleDateString() : "",
      ])
    ].map(row => row.map(field => `"${String(field || "").replace(/"/g, '""')}"`)).join("\n")

    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" })
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement("a")
    a.href = url
    a.download = "employees.csv"
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    window.URL.revokeObjectURL(url)
  }

  return (
    <div className="w-full">
      <div className="flex items-center justify-between py-4">
        <Input
          placeholder="Filter employees..."
          value={(table.getColumn("name")?.getFilterValue() as string) ?? ""}
          onChange={(event) =>
            table.getColumn("name")?.setFilterValue(event.target.value)
          }
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
        </div>
        
        <div className="text-sm text-muted-foreground">
          Showing {pagination.from} to {pagination.to} of {pagination.total} entries
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