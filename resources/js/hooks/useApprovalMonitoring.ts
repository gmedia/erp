import axios from '@/lib/axios';
import { useQuery } from '@tanstack/react-query';
import { useState } from 'react';

export interface ApprovalMonitoringFilters {
    document_type?: string;
    status?: string;
    approver_id?: string;
    start_date?: string;
    end_date?: string;
}

export interface ApprovalSummary {
    total_pending: number;
    approved_today: number;
    rejected_today: number;
    avg_processing_time_hours: number;
}

export interface OverdueApproval {
    id: number;
    request_id: number;
    document_type: string;
    document_name: string;
    submitter_name: string;
    step_name: string;
    due_at: string;
    hours_overdue: number;
}

export interface ApprovalMonitoringData {
    summary: ApprovalSummary;
    overdue_approvals: OverdueApproval[];
}

export function useApprovalMonitoring(
    initialFilters?: ApprovalMonitoringFilters,
) {
    const [filters, setFilters] = useState<ApprovalMonitoringFilters>(
        initialFilters || {},
    );

    const fetchMonitoringData = async (): Promise<ApprovalMonitoringData> => {
        const params = new URLSearchParams();

        if (filters.document_type)
            params.append('document_type', filters.document_type);
        if (filters.status) params.append('status', filters.status);
        if (filters.approver_id)
            params.append('approver_id', filters.approver_id);
        if (filters.start_date) params.append('start_date', filters.start_date);
        if (filters.end_date) params.append('end_date', filters.end_date);

        const response = await axios.get(
            `/api/approval-monitoring/data?${params.toString()}`,
        );
        return response.data;
    };

    const query = useQuery({
        queryKey: ['approval-monitoring', filters],
        queryFn: fetchMonitoringData,
        staleTime: 60000,
    });

    const handleFilterChange = (
        key: keyof ApprovalMonitoringFilters,
        value: string | undefined,
    ) => {
        setFilters((prev) => ({
            ...prev,
            [key]: value,
        }));
    };

    const resetFilters = () => {
        setFilters({});
    };

    return {
        ...query,
        filters,
        handleFilterChange,
        resetFilters,
    };
}
