import { useState } from 'react';
import axios from 'axios';
import { useQuery } from '@tanstack/react-query';

export interface PipelineDashboardFilters {
    pipeline_id?: number | string;
    entity_type?: string;
    stale_days?: number;
}

export interface StateSummary {
    state_id: number;
    name: string;
    code: string;
    color: string;
    count: number;
}

export interface StaleEntity {
    id: number;
    entity_type: string;
    entity_name: string;
    entity_id: number;
    current_state: {
        name: string;
        color: string;
    };
    days_in_state: number;
    last_transitioned_at: string;
    last_transitioned_by: string;
}

export interface PipelineDashboardData {
    summary: StateSummary[];
    stale_entities: StaleEntity[];
}

export function usePipelineDashboard(initialFilters?: PipelineDashboardFilters) {
    const [filters, setFilters] = useState<PipelineDashboardFilters>(initialFilters || { stale_days: 7 });

    const fetchDashboardData = async (): Promise<PipelineDashboardData> => {
        const params = new URLSearchParams();
        
        if (filters.pipeline_id) params.append('pipeline_id', String(filters.pipeline_id));
        if (filters.entity_type) params.append('entity_type', filters.entity_type);
        if (filters.stale_days) params.append('stale_days', String(filters.stale_days));

        const response = await axios.get(`/api/pipeline-dashboard/data?${params.toString()}`);
        return response.data;
    };

    const query = useQuery({
        queryKey: ['pipeline-dashboard', filters],
        queryFn: fetchDashboardData,
        staleTime: 60000, 
    });

    const handleFilterChange = (key: keyof PipelineDashboardFilters, value: string | number | undefined) => {
        setFilters((prev) => ({
            ...prev,
            [key]: value
        }));
    };

    const resetFilters = () => {
        setFilters({ stale_days: 7 });
    };

    return {
        ...query,
        filters,
        handleFilterChange,
        resetFilters,
    };
}
