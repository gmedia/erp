'use client';

import axiosInstance from '@/lib/axios';
import { type PeriodClosing } from '@/types/period-closing';
import { useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { useState } from 'react';
import { toast } from 'sonner';

export type PeriodClosingAction = 'close' | 'reopen';

const FALLBACK_ERROR = 'Failed to update the period. Please try again.';

export function getPeriodClosingErrorMessage(error: unknown): string {
    if (!axios.isAxiosError(error)) return FALLBACK_ERROR;

    const data = error.response?.data as
        | { message?: string; errors?: Record<string, string[]> }
        | undefined;

    return data?.errors?.status?.[0] || data?.message || FALLBACK_ERROR;
}

export function usePeriodClosingAction(options?: {
    onSuccess?: (item: PeriodClosing) => void;
    successMessages?: Record<PeriodClosingAction, string>;
}) {
    const queryClient = useQueryClient();
    const [processing, setProcessing] = useState<PeriodClosingAction | null>(
        null,
    );

    const run = async (
        item: PeriodClosing,
        action: PeriodClosingAction,
    ): Promise<boolean> => {
        setProcessing(action);
        try {
            const response = await axiosInstance.post<{ data: PeriodClosing }>(
                `/api/period-closings/${item.id}/${action}`,
            );

            toast.success(
                options?.successMessages?.[action] ??
                    (action === 'close'
                        ? 'Period closed.'
                        : 'Period reopened.'),
            );
            void queryClient.invalidateQueries({
                queryKey: ['period-closings'],
            });
            options?.onSuccess?.(response.data.data);
            return true;
        } catch (error: unknown) {
            toast.error(getPeriodClosingErrorMessage(error));
            return false;
        } finally {
            setProcessing(null);
        }
    };

    return { run, processing };
}
