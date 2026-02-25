import { useState, useCallback } from 'react';
import axios from 'axios';
import { toast } from 'sonner';
import { PipelineState } from '@/types/pipeline';
import { PipelineStateFormData } from '@/utils/schemas';

export function usePipelineState(pipelineId?: number) {
    const [states, setStates] = useState<PipelineState[]>([]);
    const [loading, setLoading] = useState(false);

    const fetchStates = useCallback(async () => {
        if (!pipelineId) return;
        
        setLoading(true);
        try {
            const response = await axios.get<{ data: PipelineState[] }>(`/api/pipelines/${pipelineId}/states`);
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
            await axios.post(`/api/pipelines/${pipelineId}/states`, data);
            toast.success('Pipeline state created successfully.');
            await fetchStates();
            return true;
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Failed to create pipeline state.');
            return false;
        }
    };

    const updateState = async (stateId: number, data: PipelineStateFormData) => {
        if (!pipelineId) return false;

        try {
            await axios.put(`/api/pipelines/${pipelineId}/states/${stateId}`, data);
            toast.success('Pipeline state updated successfully.');
            await fetchStates();
            return true;
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Failed to update pipeline state.');
            return false;
        }
    };

    const deleteState = async (stateId: number) => {
        if (!pipelineId) return false;

        try {
            await axios.delete(`/api/pipelines/${pipelineId}/states/${stateId}`);
            toast.success('Pipeline state deleted successfully.');
            await fetchStates();
            return true;
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Failed to delete pipeline state.');
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
