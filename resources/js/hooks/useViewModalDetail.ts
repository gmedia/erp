import axios from '@/lib/axios';
import { useEffect, useState } from 'react';

interface ViewModalDetailItem {
    id?: number | string | null;
    items?: unknown[] | null;
}

interface UseViewModalDetailOptions<TItem extends ViewModalDetailItem> {
    readonly endpoint: string;
    readonly open: boolean;
    readonly item: TItem | null;
}

export function useViewModalDetail<TItem extends ViewModalDetailItem>({
    endpoint,
    open,
    item,
}: Readonly<UseViewModalDetailOptions<TItem>>) {
    const [detail, setDetail] = useState<TItem | null>(null);

    useEffect(() => {
        const load = async () => {
            if (!open || !item?.id) {
                return;
            }

            if (item.items && item.items.length > 0) {
                setDetail(item);
                return;
            }

            setDetail(item);

            try {
                const response = await axios.get(`${endpoint}/${item.id}`);
                const data = response.data?.data ?? response.data;
                setDetail(data as TItem);
            } catch {
                setDetail(item);
            }
        };

        load();
    }, [endpoint, open, item]);

    return detail || item;
}
