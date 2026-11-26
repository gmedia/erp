import axiosInstance from '@/lib/axios';
import { PipelineState } from '@/types/pipeline';
import { useHookToast } from '@/utils/i18nFallback';
import { PipelineStateFormData } from '@/utils/schemas';
import axios from 'axios';
import { useCallback, useState } from 'react';
import { toast } from 'sonner';

export function usePipelineState(pipelineId?: number) {
    const [states, setStates] = useState<PipelineState[]>([]);
    const [loading, setLoading] = useState(false);
    const tToast = useHookToast();

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
            toast.error(
                tToast(
                    'pipeline_state_fetch_failed',
                    'Failed to fetch pipeline states.',
                ),
            );
        } finally {
            setLoading(false);
        }
    }, [pipelineId, tToast]);

    const createState = async (data: PipelineStateFormData) => {
        if (!pipelineId) return false;

        try {
            await axiosInstance.post(
                `/api/pipelines/${pipelineId}/states`,
                data,
            );
            toast.success(
                tToast(
                    'pipeline_state_created',
                    'Pipeline state created successfully.',
                ),
            );
            await fetchStates();
            return true;
        } catch (error: unknown) {
            if (axios.isAxiosError(error)) {
                toast.error(
                    error.response?.data?.message ||
                        tToast(
                            'pipeline_state_create_failed',
                            'Failed to create pipeline state.',
                        ),
                );
            } else {
                toast.error(
                    tToast('unexpected_error', 'An unexpected error occurred'),
                );
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
            toast.success(
                tToast(
                    'pipeline_state_updated',
                    'Pipeline state updated successfully.',
                ),
            );
            await fetchStates();
            return true;
        } catch (error: unknown) {
            if (axios.isAxiosError(error)) {
                toast.error(
                    error.response?.data?.message ||
                        tToast(
                            'pipeline_state_update_failed',
                            'Failed to update pipeline state.',
                        ),
                );
            } else {
                toast.error(
                    tToast('unexpected_error', 'An unexpected error occurred'),
                );
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
            toast.success(
                tToast(
                    'pipeline_state_deleted',
                    'Pipeline state deleted successfully.',
                ),
            );
            await fetchStates();
            return true;
        } catch (error: unknown) {
            if (axios.isAxiosError(error)) {
                toast.error(
                    error.response?.data?.message ||
                        tToast(
                            'pipeline_state_delete_failed',
                            'Failed to delete pipeline state.',
                        ),
                );
            } else {
                toast.error(
                    tToast('unexpected_error', 'An unexpected error occurred'),
                );
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
