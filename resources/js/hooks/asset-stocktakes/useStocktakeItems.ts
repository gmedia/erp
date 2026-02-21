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

export const useStocktakeItems = () => {
    const [loading, setLoading] = useState(false);
    const [items, setItems] = useState<StocktakeItem[]>([]);

    const fetchItems = useCallback(async (stocktakeId: number | string) => {
        setLoading(true);
        try {
            const response = await axios.get(
                `/api/asset-stocktakes/${stocktakeId}/items`,
            );
            // Map the data so 'result' is initialized properly for form
            const mappedItems = response.data.data.map((item: any) => ({
                ...item,
                result: item.result || '',
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
        async (stocktakeId: number | string, data: { items: StocktakeItem[] }) => {
            setLoading(true);
            try {
                await axios.post(
                    `/api/asset-stocktakes/${stocktakeId}/items`,
                    data,
                );
                toast.success('Stocktake items saved successfully.');
            } catch (error: any) {
                console.error('Failed to save items', error);
                const msg =
                    error.response?.data?.message || 'Failed to save items.';
                toast.error(msg);
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
