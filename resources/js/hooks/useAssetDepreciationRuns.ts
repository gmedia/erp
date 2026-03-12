import { useCrudQuery } from '@/hooks/useCrudQuery';
import axiosInstance from '@/lib/axios';
import { AssetDepreciationRun } from '@/types/asset-depreciation-run';
import { useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { useState } from 'react';
import { toast } from 'sonner';

export function useAssetDepreciationRuns() {
    const queryClient = useQueryClient();

    const [pagination, setPagination] = useState({ page: 1, per_page: 25 });
    const [filters, setFilters] = useState<Record<string, string | undefined>>(
        {},
    );

    const [isCalculating, setIsCalculating] = useState(false);
    const [isPosting, setIsPosting] = useState<number | null>(null);

    const { data, meta, isLoading, refetch } =
        useCrudQuery<AssetDepreciationRun>({
            endpoint: '/api/asset-depreciation-runs',
            queryKey: ['asset-depreciation-runs'],
            entityName: 'Depreciation Runs',
            pagination,
            filters,
        });

    const setPage = (page: number) => {
        setPagination((prev) => ({ ...prev, page }));
    };

    const setPerPage = (per_page: number) => {
        setPagination({ page: 1, per_page });
    };

    const handleFilterChange = (key: string, value: string | undefined) => {
        setFilters((prev) => ({ ...prev, [key]: value }));
        setPagination((prev) => ({ ...prev, page: 1 }));
    };

    const calculateDepreciation = async (formData: {
        fiscal_year_id: number;
        period_start: string;
        period_end: string;
    }) => {
        setIsCalculating(true);
        try {
            const response = await axiosInstance.post(
                '/api/asset-depreciation-runs/calculate',
                formData,
            );
            toast.success(response.data.message);
            queryClient.invalidateQueries({
                queryKey: ['asset-depreciation-runs'],
            });
            refetch();
            return true;
        } catch (error: unknown) {
            if (axios.isAxiosError(error)) {
                const message =
                    error.response?.data?.message ||
                    'Failed to calculate depreciation';
                if (error.response?.data?.errors) {
                    // Return errors to be handled by form
                    return { errors: error.response.data.errors };
                }
                toast.error(message);
            } else {
                toast.error('An unexpected error occurred');
            }
            return false;
        } finally {
            setIsCalculating(false);
        }
    };

    const postToJournal = async (id: number) => {
        setIsPosting(id);
        try {
            const response = await axiosInstance.post(
                `/api/asset-depreciation-runs/${id}/post`,
            );
            toast.success(response.data.message);
            queryClient.invalidateQueries({
                queryKey: ['asset-depreciation-runs'],
            });
            refetch();
        } catch (error: unknown) {
            if (axios.isAxiosError(error)) {
                const message =
                    error.response?.data?.message ||
                    'Failed to post to journal';
                toast.error(message);
            } else {
                toast.error('An unexpected error occurred');
            }
        } finally {
            setIsPosting(null);
        }
    };

    return {
        data,
        meta,
        isLoading,
        pagination,
        filters,
        isCalculating,
        isPosting,
        setPage,
        setPerPage,
        handleFilterChange,
        calculateDepreciation,
        postToJournal,
        refetch,
    };
}
