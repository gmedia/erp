'use client';

import { Head } from '@inertiajs/react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { useState } from 'react';
import { toast } from 'sonner';

import { PositionDataTable } from '@/components/positions/PositionDataTable';
import { PositionForm } from '@/components/positions/PositionForm';
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
import { positions } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Position, PositionFormData } from '@/types/position';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Positions',
        href: positions().url,
    },
];

export default function PositionIndex() {
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [selectedPosition, setSelectedPosition] = useState<Position | null>(
        null,
    );
    const [positionToDelete, setPositionToDelete] = useState<Position | null>(
        null,
    );

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

    // Fetch positions with pagination and filters
    const { data: positionsData, isLoading } = useQuery({
        queryKey: ['positions', pagination, filters],
        queryFn: async () => {
            try {
                const response = await axios.get('/api/positions', {
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
                toast.error('Failed to load positions');
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

    const positionsList = positionsData?.data || [];
    const meta = positionsData?.meta || {
        current_page: 1,
        per_page: 15,
        total: 0,
        last_page: 1,
    };

    // Create position mutation
    const createPositionMutation = useMutation({
        mutationFn: async (data: PositionFormData) => {
            const response = await axios.post('/api/positions', data);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['positions'] });
            setIsFormOpen(false);
            setSelectedPosition(null);
            toast.success('Position created successfully');
        },
        onError: (
            error: Error & { response?: { data?: { message?: string } } },
        ) => {
            toast.error(
                error?.response?.data?.message || 'Failed to create position',
            );
        },
    });

    // Update position mutation
    const updatePositionMutation = useMutation({
        mutationFn: async ({
            id,
            data,
        }: {
            id: number;
            data: PositionFormData;
        }) => {
            const response = await axios.put(`/api/positions/${id}`, data);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['positions'] });
            setIsFormOpen(false);
            setSelectedPosition(null);
            toast.success('Position updated successfully');
        },
        onError: (
            error: Error & { response?: { data?: { message?: string } } },
        ) => {
            toast.error(
                error?.response?.data?.message || 'Failed to update position',
            );
        },
    });

    // Delete position mutation
    const deletePositionMutation = useMutation({
        mutationFn: async (id: number) => {
            await axios.delete(`/api/positions/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['positions'] });
            setPositionToDelete(null);
            toast.success('Position deleted successfully');
        },
        onError: (
            error: Error & { response?: { data?: { message?: string } } },
        ) => {
            toast.error(
                error?.response?.data?.message || 'Failed to delete position',
            );
        },
    });

    const handleAddPosition = () => {
        setSelectedPosition(null);
        setIsFormOpen(true);
    };

    const handleEditPosition = (position: Position) => {
        setSelectedPosition(position);
        setIsFormOpen(true);
    };

    const handleDeletePosition = (position: Position) => {
        setPositionToDelete(position);
    };

    const handleFormSubmit = (data: PositionFormData) => {
        if (selectedPosition) {
            updatePositionMutation.mutate({ id: selectedPosition.id, data });
        } else {
            createPositionMutation.mutate(data);
        }
    };

    const handleDeleteConfirm = () => {
        if (positionToDelete) {
            deletePositionMutation.mutate(positionToDelete.id);
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
            <Head title="Positions" />

            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <div className="rounded-lg bg-white">
                        <PositionDataTable
                            data={positionsList}
                            onAddPosition={handleAddPosition}
                            onEditPosition={handleEditPosition}
                            onDeletePosition={handleDeletePosition}
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

            <PositionForm
                open={isFormOpen}
                onOpenChange={(open) => {
                    setIsFormOpen(open);
                    if (!open) {
                        setSelectedPosition(null);
                    }
                }}
                position={selectedPosition}
                onSubmit={handleFormSubmit}
                isLoading={
                    createPositionMutation.isPending ||
                    updatePositionMutation.isPending
                }
            />

            <AlertDialog
                open={!!positionToDelete}
                onOpenChange={(open) => !open && setPositionToDelete(null)}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            This action cannot be undone. This will permanently
                            delete <strong>{positionToDelete?.name}</strong>'s
                            position record.
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
