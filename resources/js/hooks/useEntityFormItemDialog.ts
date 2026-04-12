import { useState } from 'react';

interface UseEntityFormItemDialogOptions<TItem, TDialogItem = TItem> {
    readonly items: readonly TItem[] | undefined;
    readonly appendItem: (item: TItem) => void;
    readonly updateItem: (index: number, item: TItem) => void;
    readonly mapEditingItem?: (item: TItem | undefined) => TDialogItem | null;
}

export function useEntityFormItemDialog<TItem, TDialogItem = TItem>({
    items,
    appendItem,
    updateItem,
    mapEditingItem,
}: Readonly<UseEntityFormItemDialogOptions<TItem, TDialogItem>>) {
    const [isItemDialogOpen, setIsItemDialogOpen] = useState(false);
    const [editingIndex, setEditingIndex] = useState<number | null>(null);

    const item =
        editingIndex === null
            ? null
            : mapEditingItem
              ? mapEditingItem(items?.[editingIndex])
              : ((items?.[editingIndex] ?? null) as TDialogItem | null);

    const handleCreateNewItem = () => {
        setEditingIndex(null);
        setIsItemDialogOpen(true);
    };

    const handleEditItem = (index: number) => {
        setEditingIndex(index);
        setIsItemDialogOpen(true);
    };

    const handleItemDialogOpenChange = (open: boolean) => {
        setIsItemDialogOpen(open);
        if (open) {
            return;
        }

        setEditingIndex(null);
    };

    const handleSaveItem = (data: TItem) => {
        if (editingIndex === null) {
            appendItem(data);
        } else {
            updateItem(editingIndex, data);
        }

        setIsItemDialogOpen(false);
        setEditingIndex(null);
    };

    return {
        isItemDialogOpen,
        item,
        handleCreateNewItem,
        handleEditItem,
        handleItemDialogOpenChange,
        handleSaveItem,
    };
}
