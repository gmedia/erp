import { useState, useCallback } from 'react';
import axios from 'axios';
import { toast } from 'sonner';

export interface PipelineState {
    id: number;
    code: string;
    name: string;
    type: string;
    color: string;
    icon: string;
}

export interface PipelineTransition {
    id: number;
    name: string;
    description: string | null;
    to_state: PipelineState;
    requires_comment: boolean;
    requires_confirmation: boolean;
    is_allowed: boolean;
    rejection_reasons: string[];
}

export interface EntityStateData {
    id: number;
    entity_type: string;
    entity_id: number;
    pipeline: { id: number; name: string; code: string };
    current_state: PipelineState;
    last_transitioned_at: string | null;
    last_transitioned_by: { id: number; name: string } | null;
    available_transitions: PipelineTransition[];
}

export interface TimelineEntry {
    id: number;
    from_state: PipelineState | null;
    to_state: PipelineState | null;
    transition: { name: string } | null;
    performed_by: { name: string } | null;
    comment: string | null;
    metadata: any;
    created_at: string;
}

export function useEntityPipeline(entityType: string, entityId: string | number) {
    const [stateData, setStateData] = useState<EntityStateData | null>(null);
    const [timeline, setTimeline] = useState<TimelineEntry[]>([]);
    const [loading, setLoading] = useState(false);
    const [timelineLoading, setTimelineLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const fetchState = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await axios.get<{ data: EntityStateData }>(`/api/entity-states/${entityType}/${entityId}`);
            setStateData(response.data.data);
        } catch (err: any) {
            if (err.response?.status !== 404 && err.response?.status !== 400) {
                setError(err.response?.data?.message || 'Failed to fetch entity state');
                toast.error('Failed to fetch entity state');
            }
        } finally {
            setLoading(false);
        }
    }, [entityType, entityId]);

    const fetchTimeline = useCallback(async (page = 1) => {
        setTimelineLoading(true);
        try {
            const response = await axios.get<{ data: TimelineEntry[] }>(`/api/entity-states/${entityType}/${entityId}/timeline?page=${page}`);
            setTimeline(response.data.data);
        } catch (err: any) {
             if (err.response?.status !== 404 && err.response?.status !== 400) {
                toast.error('Failed to fetch timeline');
             }
        } finally {
            setTimelineLoading(false);
        }
    }, [entityType, entityId]);

    const executeTransition = async (transitionId: number, comment?: string) => {
        setLoading(true);
        try {
            const response = await axios.post<{ data: EntityStateData, message: string }>(`/api/entity-states/${entityType}/${entityId}/transition`, {
                transition_id: transitionId,
                comment,
            });
            setStateData(response.data.data);
            toast.success(response.data.message || 'Transition successful');
            fetchTimeline(1); // Refresh timeline after transition
            return true;
        } catch (err: any) {
            const message = err.response?.data?.message || err.response?.data?.errors?.guards?.[0] || 'Transition failed';
            toast.error(message);
            return false;
        } finally {
            setLoading(false);
        }
    };

    return {
        stateData,
        timeline,
        loading,
        timelineLoading,
        error,
        fetchState,
        fetchTimeline,
        executeTransition,
    };
}
