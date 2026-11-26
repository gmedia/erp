import axiosInstance from '@/lib/axios';
import { useHookToast } from '@/utils/i18nFallback';
import axios from 'axios';
import { useCallback, useState } from 'react';
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
    metadata: Record<string, unknown> | null;
    created_at: string;
}

export function useEntityPipeline(
    entityType: string,
    entityId: string | number,
) {
    const [stateData, setStateData] = useState<EntityStateData | null>(null);
    const [timeline, setTimeline] = useState<TimelineEntry[]>([]);
    const [loading, setLoading] = useState(false);
    const [timelineLoading, setTimelineLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const tToast = useHookToast();

    const fetchState = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await axiosInstance.get<{ data: EntityStateData }>(
                `/api/entity-states/${entityType}/${entityId}`,
            );
            setStateData(response.data.data);
        } catch (err: unknown) {
            if (axios.isAxiosError(err)) {
                if (
                    err.response?.status !== 404 &&
                    err.response?.status !== 400
                ) {
                    const fetchFailed = tToast(
                        'entity_state_fetch_failed',
                        'Failed to fetch entity state',
                    );
                    setError(err.response?.data?.message || fetchFailed);
                    toast.error(fetchFailed);
                }
            } else {
                const unexpected = tToast(
                    'unexpected_error',
                    'An unexpected error occurred',
                );
                setError(unexpected);
                toast.error(unexpected);
            }
        } finally {
            setLoading(false);
        }
    }, [entityType, entityId, tToast]);

    const fetchTimeline = useCallback(
        async (page = 1) => {
            setTimelineLoading(true);
            try {
                const response = await axiosInstance.get<{
                    data: TimelineEntry[];
                }>(
                    `/api/entity-states/${entityType}/${entityId}/timeline?page=${page}`,
                );
                setTimeline(response.data.data);
            } catch (err: unknown) {
                if (axios.isAxiosError(err)) {
                    if (
                        err.response?.status !== 404 &&
                        err.response?.status !== 400
                    ) {
                        toast.error(
                            tToast(
                                'timeline_fetch_failed',
                                'Failed to fetch timeline',
                            ),
                        );
                    }
                }
            } finally {
                setTimelineLoading(false);
            }
        },
        [entityType, entityId, tToast],
    );

    const executeTransition = async (
        transitionId: number,
        comment?: string,
    ) => {
        setLoading(true);
        try {
            const response = await axiosInstance.post<{
                data: EntityStateData;
                message: string;
            }>(`/api/entity-states/${entityType}/${entityId}/transition`, {
                transition_id: transitionId,
                comment,
            });
            setStateData(response.data.data);
            toast.success(
                response.data.message ||
                    tToast('transition_successful', 'Transition successful'),
            );
            void fetchTimeline(1); // Refresh timeline after transition
            return true;
        } catch (err: unknown) {
            if (axios.isAxiosError(err)) {
                const message =
                    err.response?.data?.message ||
                    err.response?.data?.errors?.guards?.[0] ||
                    tToast('transition_failed', 'Transition failed');
                toast.error(message);
            } else {
                toast.error(
                    tToast('unexpected_error', 'An unexpected error occurred'),
                );
            }
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
