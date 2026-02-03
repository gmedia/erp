'use client';

import { JournalEntry } from '@/types/journal-entry';
import { useCrudQuery } from '@/hooks/useCrudQuery';
import { useState } from 'react';
import axios from 'axios';
import { toast } from 'sonner';
import { useQueryClient } from '@tanstack/react-query';

export function usePostingJournal() {
    const queryClient = useQueryClient();
    const [pagination, setPagination] = useState({ page: 1, per_page: 25 });
    const [filters, setFilters] = useState({});
    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const [isPosting, setIsPosting] = useState(false);

    const { data, meta, isLoading, refetch } = useCrudQuery<JournalEntry>({
        endpoint: '/api/posting-journals',
        queryKey: ['posting-journals'],
        entityName: 'Draft Journals',
        pagination,
        filters,
    });

    const toggleSelection = (id: number) => {
        setSelectedIds((prev) =>
            prev.includes(id) ? prev.filter((i) => i !== id) : [...prev, id]
        );
    };

    const selectAll = () => {
        if (selectedIds.length === data.length) {
            setSelectedIds([]);
        } else {
            setSelectedIds(data.map((item) => item.id));
        }
    };

    const postSelected = async () => {
        if (selectedIds.length === 0) return;

        setIsPosting(true);
        try {
            const response = await axios.post('/api/posting-journals/post', {
                ids: selectedIds,
            });
            
            toast.success(response.data.message);
            setSelectedIds([]);
            queryClient.invalidateQueries({ queryKey: ['posting-journals'] });
            refetch();
        } catch (error: any) {
            const message = error.response?.data?.message || 'Failed to post journals';
            toast.error(message);
        } finally {
            setIsPosting(false);
        }
    };

    return {
        data,
        meta,
        isLoading,
        pagination,
        setPagination,
        filters,
        setFilters,
        selectedIds,
        toggleSelection,
        selectAll,
        postSelected,
        isPosting,
    };
}
