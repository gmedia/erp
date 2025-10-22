'use client';

import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { MoreHorizontal } from 'lucide-react';
import * as React from 'react';

type GenericActionsProps<T> = {
    item: T;
    onView?: (item: T) => void;
    onEdit: (item: T) => void;
    onDelete: (item: T) => void;
    extraItems?: React.ReactNode[];
};

export function GenericActions<T>({
    item,
    onView,
    onEdit,
    onDelete,
    extraItems,
}: GenericActionsProps<T>) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm">
                    Actions <MoreHorizontal className="ml-2 h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuLabel>Actions</DropdownMenuLabel>
                {extraItems?.map((node, idx) => (
                    <React.Fragment key={idx}>{node}</React.Fragment>
                ))}
                {onView && (
                    <DropdownMenuItem onClick={() => onView(item)}>
                        View
                    </DropdownMenuItem>
                )}
                <DropdownMenuItem onClick={() => onEdit(item)}>
                    Edit
                </DropdownMenuItem>
                <DropdownMenuItem
                    className="text-destructive"
                    onClick={() => onDelete(item)}
                >
                    Delete
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
