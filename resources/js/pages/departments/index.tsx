'use client';

import { Head } from '@inertiajs/react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { useState } from 'react';
import { toast } from 'sonner';

import { DepartmentDataTable } from '@/components/departments/DepartmentDataTable';
import { DepartmentForm } from '@/components/departments/DepartmentForm';
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
import AppLayout from '@/layouts/app-layout';
import { departments } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Department, DepartmentFormData } from '@/types/department';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Departments',
        href: departments().url,
    },
];

export default function DepartmentIndex() {
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [selectedDepartment, setSelectedDepartment] =
        useState<Department | null>(null);
    const [departmentToDelete, setDepartmentToDelete] =
        useState<Department | null>(null);

    // Filter states
    const [filters, setFilters] = useState({
        search: '',
        sort_by: undefined as string | undefined,
        sort_direction: undefined as string | undefined,
    });

    const [pagination, setPagination] = useState({
        page: 1,
        per_page: 15,
    });

    const queryClient = useQueryClient();

    // Fetch departments with pagination and filters
    const { data: departmentsData, isLoading } = useQuery({
        queryKey: ['departments', pagination, filters],
        queryFn: async () => {
            try {
                const response = await axios.get('/api/departments', {
                    params: {
                        page: pagination.page,
                        per_page: pagination.per_page,
                        search: filters.search,
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
                toast.error('Failed to load departments');
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

    const departmentsList = departmentsData?.data || [];
    const meta = departmentsData?.meta || {
        current_page: 1,
        per_page: 15,
        total: 0,
        last_page: 1,
    };

    // Create department mutation
    const createDepartmentMutation = useMutation({
        mutationFn: async (data: DepartmentFormData) => {
            const response = await axios.post('/api/departments', data);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['departments'] });
            setIsFormOpen(false);
            setSelectedDepartment(null);
            toast.success('Department created successfully');
        },
        onError: (
            error: Error & { response?: { data?: { message?: string } } },
        ) => {
            toast.error(
                error?.response?.data?.message || 'Failed to create department',
            );
        },
    });

    // Update department mutation
    const updateDepartmentMutation = useMutation({
        mutationFn: async ({
            id,
            data,
        }: {
            id: number;
            data: DepartmentFormData;
        }) => {
            const response = await axios.put(`/api/departments/${id}`, data);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['departments'] });
            setIsFormOpen(false);
            setSelectedDepartment(null);
            toast.success('Department updated successfully');
        },
        onError: (
            error: Error & { response?: { data?: { message?: string } } },
        ) => {
            toast.error(
                error?.response?.data?.message || 'Failed to update department',
            );
        },
    });

    // Delete department mutation
    const deleteDepartmentMutation = useMutation({
        mutationFn: async (id: number) => {
            await axios.delete(`/api/departments/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['departments'] });
            setDepartmentToDelete(null);
            toast.success('Department deleted successfully');
        },
        onError: (
            error: Error & { response?: { data?: { message?: string } } },
        ) => {
            toast.error(
                error?.response?.data?.message || 'Failed to delete department',
            );
        },
    });

    const handleAddDepartment = () => {
        setSelectedDepartment(null);
        setIsFormOpen(true);
    };

    const handleEditDepartment = (department: Department) => {
        setSelectedDepartment(department);
        setIsFormOpen(true);
    };

    const handleDeleteDepartment = (department: Department) => {
        setDepartmentToDelete(department);
    };

    const handleFormSubmit = (data: DepartmentFormData) => {
        if (selectedDepartment) {
            updateDepartmentMutation.mutate({
                id: selectedDepartment.id,
                data,
            });
        } else {
            createDepartmentMutation.mutate(data);
        }
    };

    const handleDeleteConfirm = () => {
        if (departmentToDelete) {
            deleteDepartmentMutation.mutate(departmentToDelete.id);
        }
    };

    const handleFilterChange = (newFilters: Partial<typeof filters>) => {
        setFilters((prev) => ({
            ...prev,
            ...newFilters,
        }));
        setPagination((prev) => ({ ...prev, page: 1 }));
    };

    const handleResetFilters = () => {
        setFilters({
            search: '',
            sort_by: undefined,
            sort_direction: undefined,
        });
        setPagination({ page: 1, per_page: 15 });
    };

    return (
        <>
            <Head title="Departments" />

            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <div className="rounded-lg bg-white">
                        <DepartmentDataTable
                            data={departmentsList}
                            onAddDepartment={handleAddDepartment}
                            onEditDepartment={handleEditDepartment}
                            onDeleteDepartment={handleDeleteDepartment}
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
                            filters={filters}
                            onFilterChange={handleFilterChange}
                            onResetFilters={handleResetFilters}
                        />
                    </div>
                </div>
            </AppLayout>

            <DepartmentForm
                open={isFormOpen}
                onOpenChange={(open) => {
                    setIsFormOpen(open);
                    if (!open) {
                        setSelectedDepartment(null);
                    }
                }}
                department={selectedDepartment}
                onSubmit={handleFormSubmit}
                isLoading={
                    createDepartmentMutation.isPending ||
                    updateDepartmentMutation.isPending
                }
            />

            <AlertDialog
                open={!!departmentToDelete}
                onOpenChange={(open) => !open && setDepartmentToDelete(null)}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            This action cannot be undone. This will permanently
                            delete <strong>{departmentToDelete?.name}</strong>'s
                            department record.
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
