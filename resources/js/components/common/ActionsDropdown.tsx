'use client';

import { Link } from '@inertiajs/react';
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
    viewUrl?: string;
    onEdit?: (item: T) => void;
    onDelete?: (item: T) => void;
    extraItems?: React.ReactNode[];
};

export function GenericActions<T>({
    item,
    onView,
    viewUrl,
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
                {viewUrl && (
                    <DropdownMenuItem asChild data-view-url={viewUrl}>
                        <Link href={viewUrl} className="w-full">
                            View
                        </Link>
                    </DropdownMenuItem>
                )}
                {onView && !viewUrl && (
                    <DropdownMenuItem onClick={() => onView(item)}>
                        View
                    </DropdownMenuItem>
                )}
                {onEdit && (
                    <DropdownMenuItem onClick={() => onEdit(item)}>
                        Edit
                    </DropdownMenuItem>
                )}
                {onDelete && (
                    <DropdownMenuItem
                        className="text-destructive"
                        onClick={() => onDelete(item)}
                    >
                        Delete
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
