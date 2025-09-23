import { Head } from "@inertiajs/react"
import { useState, useEffect } from "react"
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"
import axios from "axios"
import { toast } from "sonner"
import { Filter } from "lucide-react"

import { Employee } from "@/types"
import { EmployeeDataTable } from "@/components/employees/EmployeeDataTable"
import { EmployeeForm } from "@/components/employees/EmployeeForm"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Card, CardContent } from "@/components/ui/card"
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog"

export default function EmployeeIndex() {
  const [isFormOpen, setIsFormOpen] = useState(false)
  const [selectedEmployee, setSelectedEmployee] = useState<Employee | null>(null)
  const [employeeToDelete, setEmployeeToDelete] = useState<Employee | null>(null)
  const [isFilterModalOpen, setIsFilterModalOpen] = useState(false)
  
  // Filter states
  const [filters, setFilters] = useState({
    search: '',
    department: '',
    position: '',
  })
  
  // Temporary filter states for modal
  const [tempFilters, setTempFilters] = useState({
    search: '',
    department: '',
    position: '',
  })
  
  const [pagination, setPagination] = useState({
    page: 1,
    per_page: 15,
  })
  const queryClient = useQueryClient()

  // Fetch employees with pagination and filters
  const { data: employeesData, isLoading } = useQuery({
    queryKey: ["employees", pagination, filters],
    queryFn: async () => {
      try {
        const response = await axios.get("/api/employees", {
          params: {
            page: pagination.page,
            per_page: pagination.per_page,
            search: filters.search,
            department: filters.department === 'all-departments' ? '' : filters.department,
            position: filters.position === 'all-positions' ? '' : filters.position,
          },
        })
        return response.data || { data: [], meta: { current_page: 1, per_page: 15, total: 0, last_page: 1 } }
      } catch (error) {
        console.error("Failed to fetch employees:", error)
        toast.error("Failed to load employees")
        return { data: [], meta: { current_page: 1, per_page: 15, total: 0, last_page: 1 } }
      }
    },
  })

  const employees = employeesData?.data || []
  const meta = employeesData?.meta || { current_page: 1, per_page: 15, total: 0, last_page: 1 }

  // Create employee mutation
  const createEmployeeMutation = useMutation({
    mutationFn: async (data: any) => {
      const response = await axios.post("/api/employees", data)
      return response.data
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["employees"] })
      setIsFormOpen(false)
      setSelectedEmployee(null)
      toast.success("Employee created successfully")
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || "Failed to create employee")
    },
  })

  // Update employee mutation
  const updateEmployeeMutation = useMutation({
    mutationFn: async ({ id, data }: { id: number; data: any }) => {
      const response = await axios.put(`/api/employees/${id}`, data)
      return response.data
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["employees"] })
      setIsFormOpen(false)
      setSelectedEmployee(null)
      toast.success("Employee updated successfully")
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || "Failed to update employee")
    },
  })

  // Delete employee mutation
  const deleteEmployeeMutation = useMutation({
    mutationFn: async (id: number) => {
      await axios.delete(`/api/employees/${id}`)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["employees"] })
      setEmployeeToDelete(null)
      toast.success("Employee deleted successfully")
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || "Failed to delete employee")
    },
  })

  const handleAddEmployee = () => {
    setSelectedEmployee(null)
    setIsFormOpen(true)
  }

  const handleEditEmployee = (employee: Employee) => {
    setSelectedEmployee(employee)
    setIsFormOpen(true)
  }

  const handleDeleteEmployee = (employee: Employee) => {
    setEmployeeToDelete(employee)
  }

  const handleViewEmployee = (employee: Employee) => {
    // In a real app, you might navigate to a detail page or open a modal
    toast.info(`Viewing ${employee.name}'s profile`)
  }

  const handleFormSubmit = (data: any) => {
    if (selectedEmployee) {
      updateEmployeeMutation.mutate({
        id: selectedEmployee.id,
        data: {
          ...data,
          hire_date: data.hire_date.toISOString().split("T")[0],
        },
      })
    } else {
      createEmployeeMutation.mutate({
        ...data,
        hire_date: data.hire_date.toISOString().split("T")[0],
      })
    }
  }

  const handleDeleteConfirm = () => {
    if (employeeToDelete) {
      deleteEmployeeMutation.mutate(employeeToDelete.id)
    }
  }

  const handleFilterChange = (newFilters: Partial<typeof filters>) => {
    setFilters(prev => ({ ...prev, ...newFilters }))
    setPagination(prev => ({ ...prev, page: 1 }))
  }

  const handleResetFilters = () => {
    setFilters({
      search: '',
      department: '',
      position: '',
    })
    setPagination({ page: 1, per_page: 15 })
  }

  const handleOpenFilterModal = () => {
    setTempFilters({
      search: filters.search,
      department: filters.department,
      position: filters.position,
    })
    setIsFilterModalOpen(true)
  }

  const handleApplyFilters = () => {
    setFilters(tempFilters)
    setPagination(prev => ({ ...prev, page: 1 }))
    setIsFilterModalOpen(false)
  }

  const handleResetModalFilters = () => {
    setTempFilters({
      search: '',
      department: '',
      position: '',
    })
  }

  const handleResetFiltersFromModal = () => {
    handleResetFilters()
    setIsFilterModalOpen(false)
  }

  const handleCloseFilterModal = () => {
    setIsFilterModalOpen(false)
  }

  return (
    <>
      <Head title="Employees" />

      <div className="p-8">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold tracking-tight">Employees</h1>
            <p className="text-muted-foreground">
              Manage your team members and their information
            </p>
          </div>
          <div className="flex gap-2">
            <Dialog open={isFilterModalOpen} onOpenChange={setIsFilterModalOpen}>
              <DialogTrigger asChild>
                <Button variant="outline">
                  <Filter className="h-4 w-4 mr-2" />
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
                    <label className="text-sm font-medium mb-2 block">Search</label>
                    <Input
                      placeholder="Search employees..."
                      value={tempFilters.search}
                      onChange={(e) => setTempFilters(prev => ({ ...prev, search: e.target.value }))}
                    />
                  </div>
                  <div>
                    <label className="text-sm font-medium mb-2 block">Department</label>
                    <Select
                      value={tempFilters.department}
                      onValueChange={(value) => setTempFilters(prev => ({ ...prev, department: value }))}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="All departments" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all-departments">All departments</SelectItem>
                        <SelectItem value="Engineering">Engineering</SelectItem>
                        <SelectItem value="Marketing">Marketing</SelectItem>
                        <SelectItem value="Sales">Sales</SelectItem>
                        <SelectItem value="HR">HR</SelectItem>
                        <SelectItem value="Finance">Finance</SelectItem>
                        <SelectItem value="Operations">Operations</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div>
                    <label className="text-sm font-medium mb-2 block">Position</label>
                    <Select
                      value={tempFilters.position}
                      onValueChange={(value) => setTempFilters(prev => ({ ...prev, position: value }))}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="All positions" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all-positions">All positions</SelectItem>
                        <SelectItem value="Manager">Manager</SelectItem>
                        <SelectItem value="Senior Developer">Senior Developer</SelectItem>
                        <SelectItem value="Developer">Developer</SelectItem>
                        <SelectItem value="Junior Developer">Junior Developer</SelectItem>
                        <SelectItem value="Designer">Designer</SelectItem>
                        <SelectItem value="Analyst">Analyst</SelectItem>
                        <SelectItem value="Coordinator">Coordinator</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>
                <DialogFooter>
                  <Button variant="outline" onClick={handleResetModalFilters}>
                    Reset
                  </Button>
                  <Button variant="outline" onClick={handleResetFiltersFromModal}>
                    Clear All
                  </Button>
                  <Button onClick={handleApplyFilters}>
                    Apply Filters
                  </Button>
                </DialogFooter>
              </DialogContent>
            </Dialog>
            <Button onClick={handleAddEmployee}>
              Add Employee
            </Button>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow">
          <EmployeeDataTable
            data={employees}
            onAddEmployee={handleAddEmployee}
            onEditEmployee={handleEditEmployee}
            onDeleteEmployee={handleDeleteEmployee}
            onViewEmployee={handleViewEmployee}
            pagination={{
              page: meta.current_page,
              per_page: meta.per_page,
              total: meta.total,
              last_page: meta.last_page,
              from: meta.from || 0,
              to: meta.to || 0,
            }}
            onPageChange={(page) => setPagination(prev => ({ ...prev, page }))}
            onPageSizeChange={(per_page) => setPagination({ page: 1, per_page })}
            onSearchChange={(search) => handleFilterChange({ search })}
            isLoading={isLoading}
            filterValue={filters.search}
            filters={{
              search: filters.search,
              department: filters.department === 'all-departments' ? '' : filters.department,
              position: filters.position === 'all-positions' ? '' : filters.position,
            }}
          />
        </div>
      </div>

      <EmployeeForm
        open={isFormOpen}
        onOpenChange={setIsFormOpen}
        employee={selectedEmployee}
        onSubmit={handleFormSubmit}
        isLoading={createEmployeeMutation.isPending || updateEmployeeMutation.isPending}
      />

      <AlertDialog
        open={!!employeeToDelete}
        onOpenChange={(open) => !open && setEmployeeToDelete(null)}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Are you sure?</AlertDialogTitle>
            <AlertDialogDescription>
              This action cannot be undone. This will permanently delete{" "}
              <strong>{employeeToDelete?.name}</strong>'s employee record.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDeleteConfirm}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </>
  )
}
