'use client';

import { useTranslatableText } from '@/components/common/TranslatedText';
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
import { Link } from 'react-router-dom';

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
}: Readonly<GenericActionsProps<T>>) {
    const actionsLabel = useTranslatableText({
        key: 'common.actions',
        fallback: 'Actions',
    });

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-8 text-muted-foreground hover:text-foreground"
                >
                    <MoreHorizontal className="size-4" />
                    <span className="sr-only">{actionsLabel}</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuLabel>{actionsLabel}</DropdownMenuLabel>
                {extraItems && React.Children.toArray(extraItems)}
                {viewUrl && (
                    <DropdownMenuItem asChild data-view-url={viewUrl}>
                        <Link to={viewUrl} className="w-full">
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
                        className="text-destructive-foreground focus:text-destructive-foreground"
                        onClick={() => onDelete(item)}
                    >
                        Delete
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
