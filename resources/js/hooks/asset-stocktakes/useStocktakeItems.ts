import axiosInstance from '@/lib/axios';
import axios from 'axios';
import { useCallback, useState } from 'react';
import { toast } from 'sonner';

export interface StocktakeItem {
    id?: string;
    asset_id: number;
    asset?: { asset_code: string; name: string };
    expected_branch_id: number;
    expected_location_id: number | null;
    found_branch_id?: number | null;
    found_location_id?: number | null;
    result: 'found' | 'missing' | 'damaged' | 'moved' | '';
    notes?: string;
}

const normalizeStocktakeResult = (
    result: string | null | undefined,
): StocktakeItem['result'] => {
    switch (result) {
        case 'found':
        case 'missing':
        case 'damaged':
        case 'moved':
            return result;
        default:
            return '';
    }
};

export const useStocktakeItems = () => {
    const [loading, setLoading] = useState(false);
    const [items, setItems] = useState<StocktakeItem[]>([]);

    const fetchItems = useCallback(async (identifier: string | number) => {
        setLoading(true);
        try {
            const response = await axiosInstance.get(
                `/api/asset-stocktakes/${identifier}/items`,
            );
            // Map the data so 'result' is initialized properly for form
            const mappedItems: StocktakeItem[] = (
                response.data.data as StocktakeItem[]
            ).map((item) => ({
                ...item,
                result: normalizeStocktakeResult(item.result),
            }));
            setItems(mappedItems);
        } catch (error) {
            console.error('Failed to fetch items', error);
            toast.error('Failed to fetch stocktake items.');
        } finally {
            setLoading(false);
        }
    }, []);

    const saveItems = useCallback(
        async (
            identifier: string | number,
            data: { items: StocktakeItem[] },
        ) => {
            setLoading(true);
            try {
                await axiosInstance.post(
                    `/api/asset-stocktakes/${identifier}/items`,
                    data,
                );
                toast.success('Stocktake items saved successfully.');
            } catch (error: unknown) {
                console.error('Failed to save items', error);
                if (axios.isAxiosError(error)) {
                    const msg =
                        error.response?.data?.message || 'Failed to save items.';
                    toast.error(msg);
                } else {
                    toast.error('An unexpected error occurred');
                }
                throw error;
            } finally {
                setLoading(false);
            }
        },
        [],
    );

    return {
        loading,
        items,
        fetchItems,
        saveItems,
    };
};
