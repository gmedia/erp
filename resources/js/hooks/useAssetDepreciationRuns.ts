import { useOptionalTranslation } from '@/contexts/i18n-context';
import { useCrudQuery } from '@/hooks/useCrudQuery';
import axiosInstance from '@/lib/axios';
import { AssetDepreciationRun } from '@/types/asset-depreciation-run';
import { useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { useState } from 'react';
import { toast } from 'sonner';

function translateOr(
    t: (key: string) => string,
    key: string,
    fallback: string,
): string {
    const translated = t(key);

    return translated === key ? fallback : translated;
}

export function useAssetDepreciationRuns() {
    const queryClient = useQueryClient();
    const { t } = useOptionalTranslation();
    const tToast = (key: string, fallback: string) =>
        translateOr(t, `asset_depreciation_runs_toast.${key}`, fallback);
    const tErrorUnexpected = () =>
        translateOr(t, 'api_error.unexpected', 'An unexpected error occurred');

    const [pagination, setPagination] = useState({ page: 1, per_page: 15 });
    const [filters, setFilters] = useState<Record<string, string | undefined>>(
        {},
    );

    const [isCalculating, setIsCalculating] = useState(false);
    const [isPosting, setIsPosting] = useState<number | null>(null);
    const [isVoiding, setIsVoiding] = useState<number | null>(null);

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
            void queryClient.invalidateQueries({
                queryKey: ['asset-depreciation-runs'],
            });
            void refetch();
            return true;
        } catch (error: unknown) {
            if (axios.isAxiosError(error)) {
                const message =
                    error.response?.data?.message ||
                    tToast(
                        'calculate_failed',
                        'Failed to calculate depreciation',
                    );
                if (error.response?.data?.errors) {
                    return { errors: error.response.data.errors };
                }
                toast.error(message);
            } else {
                toast.error(tErrorUnexpected());
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
            void queryClient.invalidateQueries({
                queryKey: ['asset-depreciation-runs'],
            });
            void refetch();
        } catch (error: unknown) {
            if (axios.isAxiosError(error)) {
                const message =
                    error.response?.data?.message ||
                    tToast('post_failed', 'Failed to post to journal');
                toast.error(message);
            } else {
                toast.error(tErrorUnexpected());
            }
        } finally {
            setIsPosting(null);
        }
    };

    const voidRun = async (id: number) => {
        setIsVoiding(id);
        try {
            const response = await axiosInstance.post(
                `/api/asset-depreciation-runs/${id}/void`,
            );
            toast.success(response.data.message);
            void queryClient.invalidateQueries({
                queryKey: ['asset-depreciation-runs'],
            });
            void refetch();
            return true;
        } catch (error: unknown) {
            if (axios.isAxiosError(error)) {
                const message =
                    error.response?.data?.errors?.status?.[0] ||
                    error.response?.data?.message ||
                    tToast('void_failed', 'Failed to void depreciation run');
                toast.error(message);
            } else {
                toast.error(tErrorUnexpected());
            }
            return false;
        } finally {
            setIsVoiding(null);
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
        isVoiding,
        setPage,
        setPerPage,
        handleFilterChange,
        calculateDepreciation,
        postToJournal,
        voidRun,
        refetch,
    };
}
