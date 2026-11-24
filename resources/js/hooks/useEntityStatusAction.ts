'use client';

import axiosInstance from '@/lib/axios';
import { useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { useState } from 'react';
import { toast } from 'sonner';

interface UseEntityStatusActionOptions<T, A extends string> {
    endpoint: string;
    queryKey: string;
    successMessages: Record<A, string>;
    failedMessage: string;
    onSuccess?: (item: T) => void;
    request?: (action: A, id: number | string) => ActionRequest;
}

interface ActionRequest {
    method: 'post' | 'put';
    url: string;
    body?: object;
}

function getErrorMessage(error: unknown, fallback: string): string {
    if (!axios.isAxiosError(error)) return fallback;

    const data = error.response?.data as
        | { message?: string; errors?: Record<string, string[]> }
        | undefined;

    return data?.errors?.status?.[0] || data?.message || fallback;
}

/** Aksi status per dokumen: POST {endpoint}/{id}/{action}, toast, lalu segarkan daftar. */
export function useEntityStatusAction<
    T extends { id: number | string },
    A extends string,
>({
    endpoint,
    queryKey,
    successMessages,
    failedMessage,
    onSuccess,
    request,
}: UseEntityStatusActionOptions<T, A>) {
    const queryClient = useQueryClient();
    const [processing, setProcessing] = useState(false);

    const run = async (item: T, action: A): Promise<boolean> => {
        setProcessing(true);
        try {
            const { method, url, body } = request?.(action, item.id) ?? {
                method: 'post' as const,
                url: `${endpoint}/${item.id}/${action}`,
            };
            const response = await axiosInstance.request<{ data: T }>({
                method,
                url,
                data: body,
            });

            toast.success(successMessages[action]);
            void queryClient.invalidateQueries({ queryKey: [queryKey] });
            onSuccess?.(response.data.data);
            return true;
        } catch (error: unknown) {
            toast.error(getErrorMessage(error, failedMessage));
            return false;
        } finally {
            setProcessing(false);
        }
    };

    return { run, processing };
}
