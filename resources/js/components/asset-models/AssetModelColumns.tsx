'use client';

import { Badge } from '@/components/ui/badge';
import { formatStatusLabel } from '@/lib/status-badge';
import { ColumnDef } from '@tanstack/react-table';

import {
    createActionsColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';

import { AssetModel } from '@/types/entity';

/**
 * Cell renderer for category column
 */
const renderCategoryCell = ({ row }: { row: { original: AssetModel } }) => {
    const category = row.original.category;
    return <Badge variant="outline">{category?.name || '-'}</Badge>;
};

/**
 * Cell renderer for specs column
 */
const SPEC_ACRONYMS = new Set([
    'ram',
    'gb',
    'tb',
    'cpu',
    'gpu',
    'os',
    'ssd',
    'hdd',
    'usb',
    'hdmi',
    'lan',
    'wifi',
]);

const SPEC_UNIT_SUFFIXES = new Set(['gb', 'tb', 'mb', 'mhz', 'ghz']);

function splitSpecUnit(key: string): { base: string; unit: string | null } {
    const parts = key.split(/[\s_]+/).filter(Boolean);
    const last = parts[parts.length - 1]?.toLowerCase() ?? '';
    if (parts.length > 1 && SPEC_UNIT_SUFFIXES.has(last)) {
        return { base: parts.slice(0, -1).join('_'), unit: last.toUpperCase() };
    }

    return { base: key, unit: null };
}

function formatSpecLabel(base: string): string {
    return base
        .split(/[\s_]+/)
        .filter(Boolean)
        .map((word) =>
            SPEC_ACRONYMS.has(word.toLowerCase())
                ? word.toUpperCase()
                : formatStatusLabel(word),
        )
        .join(' ');
}

function formatSpecValue(value: unknown, unit: string | null): string {
    const text = String(value);
    if (unit) {
        return `${text} ${unit}`;
    }

    return /^[a-z][a-z_]*$/.test(text) ? formatStatusLabel(text) : text;
}

const renderSpecsCell = ({ row }: { row: { original: AssetModel } }) => {
    const specs = row.original.specs;
    if (!specs || typeof specs !== 'object') {
        return <span className="text-muted-foreground">-</span>;
    }

    const entries = Object.entries(specs as Record<string, unknown>);
    if (entries.length === 0) {
        return <span className="text-muted-foreground">-</span>;
    }

    return (
        <span className="text-xs text-muted-foreground">
            {entries
                .map(([key, value]) => {
                    const { base, unit } = splitSpecUnit(key);
                    return `${formatSpecLabel(base)}: ${formatSpecValue(value, unit)}`;
                })
                .join(' · ')}
        </span>
    );
};

export const assetModelColumns: ColumnDef<AssetModel>[] = [
    createSelectColumn<AssetModel>(),
    createTextColumn<AssetModel>({
        accessorKey: 'model_name',
        label: 'Model Name',
    }),
    createTextColumn<AssetModel>({
        accessorKey: 'manufacturer',
        label: 'Manufacturer',
    }),
    {
        accessorKey: 'category',
        ...createSortingHeader('Category'),
        cell: renderCategoryCell,
    },
    {
        accessorKey: 'specs',
        header: 'Specs',
        cell: renderSpecsCell,
        enableSorting: false,
    },
    createActionsColumn<AssetModel>(),
];
