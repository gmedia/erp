'use client';

import { Pencil, Plus, Trash2 } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { TableCell, TableRow } from '@/components/ui/table';
import { useTranslation } from '@/contexts/i18n-context';

interface EntityFormItemSectionHeaderProps {
    readonly onAddItem: () => void;
    readonly title?: string;
    readonly addLabel?: string;
    readonly locked?: boolean;
}

interface EntityFormItemActionsCellProps {
    readonly index: number;
    readonly onEdit: (index: number) => void;
    readonly onRemove: (index: number) => void;
    readonly locked?: boolean;
}

interface EntityFormItemEmptyRowProps {
    readonly colSpan: number;
    readonly message?: string;
    readonly className?: string;
}

export function EntityFormItemSectionHeader({
    onAddItem,
    title = 'Items',
    addLabel,
    locked = false,
}: Readonly<EntityFormItemSectionHeaderProps>) {
    const { t } = useTranslation();
    const addItemKey = 'shell.add_item';
    const translatedAddItem = t(addItemKey);
    const defaultAddLabel =
        translatedAddItem === addItemKey ? 'Add Item' : translatedAddItem;

    return (
        <div className="flex items-center justify-between">
            <div className="text-sm font-semibold">{title}</div>
            <Button
                type="button"
                variant="outline"
                onClick={onAddItem}
                disabled={locked}
            >
                <Plus className="mr-2 h-4 w-4" />
                {addLabel ?? defaultAddLabel}
            </Button>
        </div>
    );
}

export function EntityFormItemActionsCell({
    index,
    onEdit,
    onRemove,
    locked = false,
}: Readonly<EntityFormItemActionsCellProps>) {
    return (
        <TableCell className="text-right">
            <Button
                type="button"
                variant="ghost"
                size="icon"
                onClick={() => onEdit(index)}
                disabled={locked}
                title="Edit item"
                aria-label={`Edit item ${index + 1}`}
            >
                <Pencil className="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                onClick={() => onRemove(index)}
                disabled={locked}
                title="Remove item"
                aria-label={`Remove item ${index + 1}`}
            >
                <Trash2 className="h-4 w-4" />
            </Button>
        </TableCell>
    );
}

export function EntityFormItemEmptyRow({
    colSpan,
    message = 'No items added yet.',
    className = 'py-6 text-center text-muted-foreground',
}: Readonly<EntityFormItemEmptyRowProps>) {
    return (
        <TableRow>
            <TableCell colSpan={colSpan} className={className}>
                {message}
            </TableCell>
        </TableRow>
    );
}
