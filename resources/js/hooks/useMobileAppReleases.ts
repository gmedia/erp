import { useTranslation } from '@/contexts/i18n-context';
import axios from '@/lib/axios';
import {
    type MobileAppRelease,
    type MobileAppReleaseListResponse,
} from '@/types/mobile-app';
import { handleApiError } from '@/utils/errorHandling';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';

const LIST_QUERY_KEY = ['mobile-app-releases'];
const ACTIVE_QUERY_KEY = ['mobile-app-active'];

export interface MobileAppReleaseListParams {
    page: number;
    perPage: number;
    search?: string;
}

export function useMobileAppReleases({
    page,
    perPage,
    search,
}: MobileAppReleaseListParams) {
    return useQuery<MobileAppReleaseListResponse>({
        queryKey: [...LIST_QUERY_KEY, { page, perPage, search }],
        queryFn: async () => {
            const response = await axios.get('/api/mobile-app-releases', {
                params: {
                    page,
                    per_page: perPage,
                    search: search?.trim() ? search.trim() : undefined,
                },
            });

            return response.data as MobileAppReleaseListResponse;
        },
        staleTime: 15_000,
    });
}

export function useActiveMobileAppRelease() {
    return useQuery<MobileAppRelease | null>({
        queryKey: ACTIVE_QUERY_KEY,
        queryFn: async () => {
            const response = await axios.get('/api/mobile-app');
            const release = response.data?.data ?? null;

            return (release as MobileAppRelease | null) ?? null;
        },
        staleTime: 15_000,
    });
}

export function useCreateMobileAppRelease(
    onProgress?: (percent: number) => void,
) {
    const queryClient = useQueryClient();
    const { t } = useTranslation();

    return useMutation<MobileAppRelease, Error, FormData>({
        mutationFn: async (payload) => {
            const response = await axios.post(
                '/api/mobile-app-releases',
                payload,
                {
                    onUploadProgress: (event) => {
                        if (!event.total) return;
                        const percent = Math.round(
                            (event.loaded / event.total) * 100,
                        );
                        onProgress?.(percent);
                    },
                },
            );

            return response.data as MobileAppRelease;
        },
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: LIST_QUERY_KEY });
            void queryClient.invalidateQueries({ queryKey: ACTIVE_QUERY_KEY });
            toast.success(t('mobileApp.upload_success'));
        },
        onError: (error) => {
            handleApiError(error, t('mobileApp.upload_failed'));
        },
    });
}

export function useSetMobileAppReleaseActive() {
    const queryClient = useQueryClient();
    const { t } = useTranslation();

    return useMutation<
        MobileAppRelease,
        Error,
        { id: number; active: boolean }
    >({
        mutationFn: async ({ id, active }) => {
            const action = active ? 'activate' : 'deactivate';
            const response = await axios.patch(
                `/api/mobile-app-releases/${id}/${action}`,
            );

            return response.data as MobileAppRelease;
        },
        onSuccess: (_release, variables) => {
            void queryClient.invalidateQueries({
                queryKey: LIST_QUERY_KEY,
            });
            void queryClient.invalidateQueries({
                queryKey: ACTIVE_QUERY_KEY,
            });
            toast.success(
                t(
                    variables.active
                        ? 'mobileApp.activate_success'
                        : 'mobileApp.deactivate_success',
                ),
            );
        },
        onError: (error) => {
            handleApiError(error, t('mobileApp.action_failed'));
        },
    });
}

function filenameFromDisposition(
    disposition: string | undefined,
    fallback: string,
): string {
    if (!disposition) return fallback;

    const utf8Match = disposition.match(/filename\*=UTF-8''([^;]+)/i);
    if (utf8Match?.[1]) {
        return decodeURIComponent(utf8Match[1]);
    }

    const plainMatch = disposition.match(/filename="?([^";]+)"?/i);
    if (plainMatch?.[1]) {
        return plainMatch[1];
    }

    return fallback;
}

export async function downloadActiveMobileAppRelease(
    fallbackFilename: string,
): Promise<void> {
    const response = await axios.get('/api/mobile-app/download', {
        responseType: 'blob',
    });

    const filename = filenameFromDisposition(
        response.headers['content-disposition'] as string | undefined,
        fallbackFilename,
    );
    const url = window.URL.createObjectURL(response.data as Blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = filename;
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    window.URL.revokeObjectURL(url);
}
