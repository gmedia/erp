import { useState, useCallback } from 'react';
import axios from 'axios';
import { toast } from 'sonner';
import { PipelineTransition } from '@/types/pipeline';
import { PipelineTransitionFormData } from '@/utils/schemas';

export function usePipelineTransition(pipelineId: number) {
    const [transitions, setTransitions] = useState<PipelineTransition[]>([]);
    const [loading, setLoading] = useState(false);

    const fetchTransitions = useCallback(async () => {
        setLoading(true);
        try {
            const response = await axios.get(`/api/pipelines/${pipelineId}/transitions`);
            setTransitions(response.data.data);
        } catch (error: any) {
            toast.error('Failed to fetch pipeline transitions. Please try again.');
        } finally {
            setLoading(false);
        }
    }, [pipelineId]);

    const createTransition = async (data: PipelineTransitionFormData) => {
        try {
            const response = await axios.post(`/api/pipelines/${pipelineId}/transitions`, data);
            setTransitions(prev => [...prev, response.data.data]);
            toast.success('Pipeline transition created successfully.');
            return true;
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Failed to create pipeline transition.');
            return false;
        }
    };

    const updateTransition = async (id: number, data: PipelineTransitionFormData) => {
        try {
            const response = await axios.put(`/api/pipelines/${pipelineId}/transitions/${id}`, data);
            setTransitions(prev => prev.map(t => (t.id === id ? response.data.data : t)));
            toast.success('Pipeline transition updated successfully.');
            return true;
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Failed to update pipeline transition.');
            return false;
        }
    };

    const deleteTransition = async (id: number) => {
        try {
            await axios.delete(`/api/pipelines/${pipelineId}/transitions/${id}`);
            setTransitions(prev => prev.filter(t => t.id !== id));
            toast.success('Pipeline transition deleted successfully.');
            return true;
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Failed to delete pipeline transition.');
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
