import axiosInstance from '@/lib/axios';
import { PipelineTransition } from '@/types/pipeline';
import { useHookToast } from '@/utils/i18nFallback';
import { PipelineTransitionFormData } from '@/utils/schemas';
import axios from 'axios';
import { useCallback, useState } from 'react';
import { toast } from 'sonner';

export function usePipelineTransition(pipelineId: number) {
    const [transitions, setTransitions] = useState<PipelineTransition[]>([]);
    const [loading, setLoading] = useState(false);
    const tToast = useHookToast();

    const fetchTransitions = useCallback(async () => {
        setLoading(true);
        try {
            const response = await axiosInstance.get(
                `/api/pipelines/${pipelineId}/transitions`,
            );
            setTransitions(response.data.data);
        } catch {
            toast.error(
                tToast(
                    'pipeline_transition_fetch_failed',
                    'Failed to fetch pipeline transitions. Please try again.',
                ),
            );
        } finally {
            setLoading(false);
        }
    }, [pipelineId, tToast]);

    const createTransition = async (data: PipelineTransitionFormData) => {
        try {
            const response = await axiosInstance.post(
                `/api/pipelines/${pipelineId}/transitions`,
                data,
            );
            setTransitions((prev) => [...prev, response.data.data]);
            toast.success(
                tToast(
                    'pipeline_transition_created',
                    'Pipeline transition created successfully.',
                ),
            );
            return true;
        } catch (error: unknown) {
            if (axios.isAxiosError(error)) {
                toast.error(
                    error.response?.data?.message ||
                        tToast(
                            'pipeline_transition_create_failed',
                            'Failed to create pipeline transition.',
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

    const updateTransition = async (
        id: number,
        data: PipelineTransitionFormData,
    ) => {
        try {
            const response = await axiosInstance.put(
                `/api/pipelines/${pipelineId}/transitions/${id}`,
                data,
            );
            setTransitions((prev) =>
                prev.map((t) => (t.id === id ? response.data.data : t)),
            );
            toast.success(
                tToast(
                    'pipeline_transition_updated',
                    'Pipeline transition updated successfully.',
                ),
            );
            return true;
        } catch (error: unknown) {
            if (axios.isAxiosError(error)) {
                toast.error(
                    error.response?.data?.message ||
                        tToast(
                            'pipeline_transition_update_failed',
                            'Failed to update pipeline transition.',
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

    const deleteTransition = async (id: number) => {
        try {
            await axiosInstance.delete(
                `/api/pipelines/${pipelineId}/transitions/${id}`,
            );
            setTransitions((prev) => prev.filter((t) => t.id !== id));
            toast.success(
                tToast(
                    'pipeline_transition_deleted',
                    'Pipeline transition deleted successfully.',
                ),
            );
            return true;
        } catch (error: unknown) {
            if (axios.isAxiosError(error)) {
                toast.error(
                    error.response?.data?.message ||
                        tToast(
                            'pipeline_transition_delete_failed',
                            'Failed to delete pipeline transition.',
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
        transitions,
        loading,
        fetchTransitions,
        createTransition,
        updateTransition,
        deleteTransition,
    };
}
