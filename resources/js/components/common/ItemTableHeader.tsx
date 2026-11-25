'use client';

import { TableHead, TableHeader, TableRow } from '@/components/ui/table';

export interface ItemTableColumn {
    readonly key: string;
    readonly fallback: string;
    readonly className?: string;
    readonly label?: string;
}

interface ItemTableHeaderProps {
    readonly columns: readonly ItemTableColumn[];
    readonly translate: (key: string, fallback: string) => string;
}

export function ItemTableHeader({
    columns,
    translate,
}: Readonly<ItemTableHeaderProps>) {
    return (
        <TableHeader>
            <TableRow>
                {columns.map((column) => (
                    <TableHead key={column.key} className={column.className}>
                        {column.label ?? translate(column.key, column.fallback)}
                    </TableHead>
                ))}
            </TableRow>
        </TableHeader>
    );
}
