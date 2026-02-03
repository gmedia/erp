'use client';

import { JournalEntry } from '@/types/journal-entry';
import { useCrudQuery } from '@/hooks/useCrudQuery';
import { useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import { toast } from 'sonner';
import { useQueryClient } from '@tanstack/react-query';

type CheckboxCheckedState = boolean | 'indeterminate';

export function usePostingJournal() {
    const queryClient = useQueryClient();

    const [pagination, setPagination] = useState({ page: 1, per_page: 25 });
    const [filters, setFilters] = useState<Record<string, string | undefined>>({});
    const [searchQuery, setSearchQuery] = useState('');

    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const [isPosting, setIsPosting] = useState(false);

    const { data, meta, isLoading, refetch } = useCrudQuery<JournalEntry>({
        endpoint: '/api/posting-journals',
        queryKey: ['posting-journals'],
        entityName: 'Draft Journals',
        pagination,
        filters,
    });

    const dataIds = useMemo(() => new Set(data.map((d) => d.id)), [data]);

    useEffect(() => {
        if (isLoading) return;
        setSelectedIds((prev) => prev.filter((id) => dataIds.has(id)));
    }, [dataIds, isLoading]);

    useEffect(() => {
        const trimmed = searchQuery.trim();
        const timeout = setTimeout(() => {
            setFilters((prev) => ({
                ...prev,
                search: trimmed.length > 0 ? trimmed : undefined,
            }));
            setPagination((prev) => ({ ...prev, page: 1 }));
            setSelectedIds([]);
        }, 300);

        return () => clearTimeout(timeout);
    }, [searchQuery]);

    const toggleSelection = (id: number) => {
        setSelectedIds((prev) =>
            prev.includes(id) ? prev.filter((i) => i !== id) : [...prev, id],
        );
    };

    const selectAll = (checked: CheckboxCheckedState) => {
        if (checked === true) {
            setSelectedIds(data.map((item) => item.id));
        } else {
            setSelectedIds([]);
        }
    };

    const clearSelection = () => setSelectedIds([]);

    const setPage = (page: number) => {
        setPagination((prev) => ({ ...prev, page }));
        setSelectedIds([]);
    };

    const setPerPage = (per_page: number) => {
        setPagination({ page: 1, per_page });
        setSelectedIds([]);
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
            const message =
                error.response?.data?.message || 'Failed to post journals';
            toast.error(message);
        } finally {
            setIsPosting(false);
        }
    };

    const handleSearch = (query: string) => {
        setSearchQuery(query);
    };

    return {
        data,
        meta,
        isLoading,
        pagination,
        filters,
        searchQuery,
        selectedIds,
        toggleSelection,
        selectAll,
        clearSelection,
        postSelected,
        isPosting,
        handleSearch,
        setPage,
        setPerPage,
        refetch,
    };
}
