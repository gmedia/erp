'use client';

import { EntityActions } from '@/components/common/EntityActions';
import { createSelectColumn } from '@/components/common/SelectColumn';
import { Button } from '@/components/ui/button';
import { ArrowUpDown } from 'lucide-react';

/**
 * Returns a column definition that renders a sorting header with a ghost button
 * and an ArrowUpDown icon.
 *
 * @param label - The column header label.
 */
export function createSortingHeader<T>(label: string) {
    return {
        header: ({ column }: { column: any }) => (
            <Button
                variant="ghost"
                onClick={() =>
                    column.toggleSorting(column.getIsSorted() === 'asc')
                }
            >
                {label}
                <ArrowUpDown className="ml-2 h-4 w-4" />
            </Button>
        ),
    };
}

/**
 * Returns an actions column that renders the shared {@link EntityActions}
 * component. The caller supplies callbacks for edit/delete and optionally view.
 */
export function createActionsColumn<T>(options: {
    onEdit: (row: T) => void;
    onDelete: (row: T) => void;
    onView?: (row: T) => void;
}) {
    return {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }: { row: any }) => {
            const item = row.original as T;
            return (
                <EntityActions
                    item={item}
                    onView={options.onView}
                    onEdit={() => options.onEdit(item)}
                    onDelete={() => options.onDelete(item)}
                />
            );
        },
    };
}

/**
 * Export the existing select‑column helper so callers can import everything
 * from a single file.
 */
export { createSelectColumn };