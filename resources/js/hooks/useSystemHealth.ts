import axios from '@/lib/axios';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

export type SystemHealthStatus = 'ok' | 'warning' | 'failed' | 'crashed' | 'skipped' | 'unknown';

export interface SystemHealthComponent {
    name: string;
    label: string;
    status: SystemHealthStatus;
    summary: string | null;
    message: string | null;
    meta: Record<string, unknown>;
}

export interface SystemHealthHistoryItem {
    check: string;
    status: SystemHealthStatus;
    message: string | null;
    checked_at: string;
}

export interface SystemHealthData {
    checked_at: string | null;
    overall: 'ok' | 'warning' | 'failed' | 'unknown';
    components: SystemHealthComponent[];
    history: SystemHealthHistoryItem[];
    thresholds: Record<string, number>;
    version: {
        app: string;
        php: string;
        laravel: string;
    };
}

const QUERY_KEY = ['system-health'];

export function useSystemHealth() {
    return useQuery<SystemHealthData>({
        queryKey: QUERY_KEY,
        queryFn: async () => {
            const response = await axios.get('/api/system-health');
            return response.data;
        },
        staleTime: 30_000,
    });
}

export function useRunSystemHealthCheck() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async () => {
            const response = await axios.post('/api/system-health/run');
            return response.data as SystemHealthData & { message: string };
        },
        onSuccess: (data) => {
            queryClient.setQueryData<SystemHealthData>(QUERY_KEY, data);
        },
    });
}
