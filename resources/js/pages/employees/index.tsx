import { Head } from '@inertiajs/react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { useState } from 'react';
import { toast } from 'sonner';

import { EmployeeDataTable } from '@/components/employees/EmployeeDataTable';
import { EmployeeForm } from '@/components/employees/EmployeeForm';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Employee, EmployeeFormData } from '@/types';
import { type BreadcrumbItem } from '@/types';
import { employees } from '@/routes';
import AppLayout from '@/layouts/app-layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Employees',
        href: employees().url,
    },
];

export default function EmployeeIndex() {
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [selectedEmployee, setSelectedEmployee] = useState<Employee | null>(
        null,
    );
    const [employeeToDelete, setEmployeeToDelete] = useState<Employee | null>(
        null,
    );

    // Filter states
    const [filters, setFilters] = useState({
        search: '',
        department: '',
        position: '',
        sort_by: undefined as string | undefined,
        sort_direction: undefined as string | undefined,
    });

    const [pagination, setPagination] = useState({
        page: 1,
        per_page: 15,
    });
    const queryClient = useQueryClient();

    // Fetch employees with pagination and filters
    const { data: employeesData, isLoading } = useQuery({
        queryKey: ['employees', pagination, filters],
        queryFn: async () => {
            try {
                const response = await axios.get('/api/employees', {
                    params: {
                        page: pagination.page,
                        per_page: pagination.per_page,
                        search: filters.search,
                        department:
                            filters.department === 'all-departments'
                                ? ''
                                : filters.department,
                        position:
                            filters.position === 'all-positions'
                                ? ''
                                : filters.position,
                        sort_by: filters.sort_by,
                        sort_direction: filters.sort_direction,
                    },
                });
                return (
                    response.data || {
                        data: [],
                        meta: {
                            current_page: 1,
                            per_page: 15,
                            total: 0,
                            last_page: 1,
                        },
                    }
                );
            } catch {
                toast.error('Failed to load employees');
                return {
                    data: [],
                    meta: {
                        current_page: 1,
                        per_page: 15,
                        total: 0,
                        last_page: 1,
                    },
                };
            }
        },
    });

    const employees = employeesData?.data || [];
    const meta = employeesData?.meta || {
        current_page: 1,
        per_page: 15,
        total: 0,
        last_page: 1,
    };

    // Create employee mutation
    const createEmployeeMutation = useMutation({
        mutationFn: async (data: EmployeeFormData) => {
            const hireDate =
                data.hire_date instanceof Date
                    ? data.hire_date
                    : new Date(data.hire_date);
            const formattedHireDate = hireDate.toISOString().split('T')[0];

            const apiData = {
                name: data.name,
                email: data.email,
                phone: data.phone,
                department: data.department,
                position: data.position,
                salary: data.salary,
                hire_date: formattedHireDate,
            };

            const response = await axios.post('/api/employees', apiData);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['employees'] });
            setIsFormOpen(false);
            setSelectedEmployee(null);
            toast.success('Employee created successfully');
        },
        onError: (
            error: Error & { response?: { data?: { message?: string } } },
        ) => {
            toast.error(
                error.response?.data?.message || 'Failed to create employee',
            );
        },
    });

    // Update employee mutation
    const updateEmployeeMutation = useMutation({
        mutationFn: async ({
            id,
            data,
        }: {
            id: number;
            data: EmployeeFormData;
        }) => {
            const hireDate =
                data.hire_date instanceof Date
                    ? data.hire_date
                    : new Date(data.hire_date);
            const formattedHireDate = hireDate.toISOString().split('T')[0];

            const apiData = {
                name: data.name,
                email: data.email,
                phone: data.phone,
                department: data.department,
                position: data.position,
                salary: data.salary,
                hire_date: formattedHireDate,
            };

            const response = await axios.put(`/api/employees/${id}`, apiData);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['employees'] });
            setIsFormOpen(false);
            setSelectedEmployee(null);
            toast.success('Employee updated successfully');
        },
        onError: (
            error: Error & { response?: { data?: { message?: string } } },
        ) => {
            toast.error(
                error.response?.data?.message || 'Failed to update employee',
            );
        },
    });

    // Delete employee mutation
    const deleteEmployeeMutation = useMutation({
        mutationFn: async (id: number) => {
            await axios.delete(`/api/employees/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['employees'] });
            setEmployeeToDelete(null);
            toast.success('Employee deleted successfully');
        },
        onError: (
            error: Error & { response?: { data?: { message?: string } } },
        ) => {
            toast.error(
                error.response?.data?.message || 'Failed to delete employee',
            );
        },
    });

    const handleAddEmployee = () => {
        setSelectedEmployee(null);
        setIsFormOpen(true);
    };

    const handleEditEmployee = (employee: Employee) => {
        setSelectedEmployee(employee);
        setIsFormOpen(true);
    };

    const handleDeleteEmployee = (employee: Employee) => {
        setEmployeeToDelete(employee);
    };

    const handleViewEmployee = (employee: Employee) => {
        // In a real app, you might navigate to a detail page or open a modal
        toast.info(`Viewing ${employee.name}'s profile`);
    };

    const handleFormSubmit = (data: EmployeeFormData) => {
        if (selectedEmployee) {
            updateEmployeeMutation.mutate({
                id: selectedEmployee.id,
                data: data,
            });
        } else {
            createEmployeeMutation.mutate(data);
        }
    };

    const handleDeleteConfirm = () => {
        if (employeeToDelete) {
            deleteEmployeeMutation.mutate(employeeToDelete.id);
        }
    };

    const handleFilterChange = (newFilters: Partial<typeof filters>) => {
        // If only the search term is being updated, reset other filters to avoid
        // unintended combination of search with stale department/position filters.
        const isSearchOnly = Object.keys(newFilters).length === 1 && 'search' in newFilters;
        setFilters((prev) => ({
            ...prev,
            ...newFilters,
            ...(isSearchOnly ? { department: '', position: '' } : {}),
        }));
        setPagination((prev) => ({ ...prev, page: 1 }));
    };

    const handleResetFilters = () => {
        setFilters({
            search: '',
            department: '',
            position: '',
            sort_by: undefined,
            sort_direction: undefined,
        });
        setPagination({ page: 1, per_page: 15 });
    };

    return (
        <>
            <Head title="Employees" />

            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <div className="rounded-lg bg-white">
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
                            onPageChange={(page) =>
                                setPagination((prev) => ({ ...prev, page }))
                            }
                            onPageSizeChange={(per_page) =>
                                setPagination({ page: 1, per_page })
                            }
                            onSearchChange={(search) =>
                                handleFilterChange({ search })
                            }
                            isLoading={isLoading}
                            filterValue={filters.search}
                            filters={{
                                search: filters.search,
                                department:
                                    filters.department === 'all-departments'
                                        ? ''
                                        : filters.department,
                                position:
                                    filters.position === 'all-positions'
                                        ? ''
                                        : filters.position,
                                sort_by: filters.sort_by,
                                sort_direction: filters.sort_direction,
                            }}
                            onFilterChange={handleFilterChange}
                            onResetFilters={handleResetFilters}
                        />
                    </div>
                </div>
            </AppLayout>

            <EmployeeForm
                open={isFormOpen}
                onOpenChange={setIsFormOpen}
                employee={selectedEmployee}
                onSubmit={handleFormSubmit}
                isLoading={
                    createEmployeeMutation.isPending ||
                    updateEmployeeMutation.isPending
                }
            />

            <AlertDialog
                open={!!employeeToDelete}
                onOpenChange={(open) => !open && setEmployeeToDelete(null)}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            This action cannot be undone. This will permanently
                            delete <strong>{employeeToDelete?.name}</strong>'s
                            employee record.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={handleDeleteConfirm}>
                            Delete
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
