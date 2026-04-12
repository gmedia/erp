'use client';

import { Pencil, Plus, Trash2 } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { TableCell, TableRow } from '@/components/ui/table';

interface EntityFormItemSectionHeaderProps {
    readonly onAddItem: () => void;
    readonly title?: string;
    readonly addLabel?: string;
}

interface EntityFormItemActionsCellProps {
    readonly index: number;
    readonly onEdit: (index: number) => void;
    readonly onRemove: (index: number) => void;
}

interface EntityFormItemEmptyRowProps {
    readonly colSpan: number;
    readonly message?: string;
    readonly className?: string;
}

export function EntityFormItemSectionHeader({
    onAddItem,
    title = 'Items',
    addLabel = 'Add Item',
}: Readonly<EntityFormItemSectionHeaderProps>) {
    return (
        <div className="flex items-center justify-between">
            <div className="text-sm font-semibold">{title}</div>
            <Button type="button" variant="outline" onClick={onAddItem}>
                <Plus className="mr-2 h-4 w-4" />
                {addLabel}
            </Button>
        </div>
    );
}

export function EntityFormItemActionsCell({
    index,
    onEdit,
    onRemove,
}: Readonly<EntityFormItemActionsCellProps>) {
    return (
        <TableCell className="text-right">
            <Button
                type="button"
                variant="ghost"
                size="icon"
                onClick={() => onEdit(index)}
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
