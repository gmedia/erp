import axiosInstance from '@/lib/axios';
import { PipelineState } from '@/types/pipeline';
import { PipelineStateFormData } from '@/utils/schemas';
import axios from 'axios';
import { useCallback, useState } from 'react';
import { toast } from 'sonner';

export function usePipelineState(pipelineId?: number) {
    const [states, setStates] = useState<PipelineState[]>([]);
    const [loading, setLoading] = useState(false);

    const fetchStates = useCallback(async () => {
        if (!pipelineId) return;

        setLoading(true);
        try {
            const response = await axiosInstance.get<{ data: PipelineState[] }>(
                `/api/pipelines/${pipelineId}/states`,
            );
            setStates(response.data.data);
        } catch (error) {
            console.error('Failed to fetch pipeline states:', error);
            toast.error('Failed to fetch pipeline states.');
        } finally {
            setLoading(false);
        }
    }, [pipelineId]);

    const createState = async (data: PipelineStateFormData) => {
        if (!pipelineId) return false;

        try {
            await axiosInstance.post(
                `/api/pipelines/${pipelineId}/states`,
                data,
            );
            toast.success('Pipeline state created successfully.');
            await fetchStates();
            return true;
        } catch (error: unknown) {
            if (axios.isAxiosError(error)) {
                toast.error(
                    error.response?.data?.message ||
                        'Failed to create pipeline state.',
                );
            } else {
                toast.error('An unexpected error occurred');
            }
            return false;
        }
    };

    const updateState = async (
        stateId: number,
        data: PipelineStateFormData,
    ) => {
        if (!pipelineId) return false;

        try {
            await axiosInstance.put(
                `/api/pipelines/${pipelineId}/states/${stateId}`,
                data,
            );
            toast.success('Pipeline state updated successfully.');
            await fetchStates();
            return true;
        } catch (error: unknown) {
            if (axios.isAxiosError(error)) {
                toast.error(
                    error.response?.data?.message ||
                        'Failed to update pipeline state.',
                );
            } else {
                toast.error('An unexpected error occurred');
            }
            return false;
        }
    };

    const deleteState = async (stateId: number) => {
        if (!pipelineId) return false;

        try {
            await axiosInstance.delete(
                `/api/pipelines/${pipelineId}/states/${stateId}`,
            );
            toast.success('Pipeline state deleted successfully.');
            await fetchStates();
            return true;
        } catch (error: unknown) {
            if (axios.isAxiosError(error)) {
                toast.error(
                    error.response?.data?.message ||
                        'Failed to delete pipeline state.',
                );
            } else {
                toast.error('An unexpected error occurred');
            }
            return false;
        }
    };

    return {
        states,
        loading,
        fetchStates,
        createState,
        updateState,
        deleteState,
    };
}
