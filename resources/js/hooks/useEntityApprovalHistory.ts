import axios from '@/lib/axios';
import { ApprovalRequest } from '@/types/approval';
import { useCallback, useState } from 'react';
import { toast } from 'sonner';

interface UseEntityApprovalHistoryProps {
    entityType: string;
    entityId: string | number;
}

export function useEntityApprovalHistory({
    entityType,
    entityId,
}: UseEntityApprovalHistoryProps) {
    const [history, setHistory] = useState<ApprovalRequest[]>([]);
    const [loading, setLoading] = useState(false);

    const fetchHistory = useCallback(async () => {
        setLoading(true);
        try {
            const response = await axios.get(
                `/api/entity-states/${entityType}/${entityId}/approvals`,
            );
            setHistory(response.data.data);
        } catch (error) {
            console.error('Failed to fetch approval history:', error);
            toast.error('Failed to load approval history');
        } finally {
            setLoading(false);
        }
    }, [entityType, entityId]);

    return {
        history,
        loading,
        fetchHistory,
    };
}
