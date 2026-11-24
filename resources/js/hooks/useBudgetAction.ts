'use client';

import { useTranslation } from '@/contexts/i18n-context';
import axiosInstance from '@/lib/axios';
import { type Budget } from '@/types/budget';
import { useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { useState } from 'react';
import { toast } from 'sonner';

export type BudgetAction = 'approve' | 'lock';

export function getBudgetErrorMessage(
    error: unknown,
    fallback: string,
): string {
    if (!axios.isAxiosError(error)) return fallback;

    const data = error.response?.data as
        | { message?: string; errors?: Record<string, string[]> }
        | undefined;

    return data?.errors?.status?.[0] || data?.message || fallback;
}

export function useBudgetAction(options?: {
    onSuccess?: (item: Budget) => void;
}) {
    const { t } = useTranslation();
    const queryClient = useQueryClient();
    const [processing, setProcessing] = useState<BudgetAction | null>(null);

    const run = async (
        item: Budget,
        action: BudgetAction,
    ): Promise<boolean> => {
        setProcessing(action);
        try {
            const response = await axiosInstance.post<{ data: Budget }>(
                `/api/budgets/${item.id}/${action}`,
            );

            toast.success(
                t(action === 'approve' ? 'budgets.approved' : 'budgets.locked'),
            );
            void queryClient.invalidateQueries({ queryKey: ['budgets'] });
            options?.onSuccess?.(response.data.data);
            return true;
        } catch (error: unknown) {
            toast.error(
                getBudgetErrorMessage(error, t('budgets.actionFailed')),
            );
            return false;
        } finally {
            setProcessing(null);
        }
    };

    return { run, processing };
}
