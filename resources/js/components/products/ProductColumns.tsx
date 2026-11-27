'use client';

import { LabelText } from '@/components/common/TranslatedText';
import { Badge } from '@/components/ui/badge';
import { formatStatusLabel, statusBadgeVariant } from '@/lib/status-badge';
import {
    createActionsColumn,
    createCurrencyColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';

import { Product } from '@/types/entity';
import { FORMAT_LOCALE } from '@/utils/formatters';

/**
 * Cell renderer for category column
 */
const renderCategoryCell = ({ row }: { row: { original: Product } }) => {
    const val = row.original.category;
    return <div>{typeof val === 'object' ? val.name : val}</div>;
};

/**
 * Cell renderer for type column with badge
 */
const PRODUCT_TYPE_LABELS: Record<
    string,
    { key: string; fallback: string }
> = {
    raw_material: {
        key: 'form_fields.products.type_raw_material',
        fallback: 'Raw Material',
    },
    work_in_progress: {
        key: 'form_fields.products.type_work_in_progress',
        fallback: 'WIP',
    },
    finished_good: {
        key: 'form_fields.products.type_finished_good',
        fallback: 'Finished Good',
    },
    purchased_good: {
        key: 'form_fields.products.type_purchased_good',
        fallback: 'Purchased Good',
    },
    service: {
        key: 'form_fields.products.type_service',
        fallback: 'Service',
    },
};

const renderTypeCell = ({ row }: { row: { original: Product } }) => {
    const type = row.original.type;
    const label = PRODUCT_TYPE_LABELS[type];

    return (
        <Badge variant="secondary">
            {label ? <LabelText label={label} /> : type}
        </Badge>
    );
};

/**
 * Cell renderer for status column with badge
 */
const renderStatusCell = ({ row }: { row: { original: Product } }) => {
    const status = row.original.status;
    return (
        <Badge variant={statusBadgeVariant(status)}>
            {formatStatusLabel(status)}
        </Badge>
    );
};

export const productColumns: ColumnDef<Product>[] = [
    createSelectColumn(),
    createTextColumn<Product>({
        accessorKey: 'code',
        label: { key: 'column.code', fallback: 'Code' },
    }),
    createTextColumn<Product>({
        accessorKey: 'name',
        label: { key: 'column.name', fallback: 'Name' },
        primary: true,
        description: (row) => row.category?.name ?? null,
    }),
    {
        accessorKey: 'type',
        ...createSortingHeader({ key: 'column.type', fallback: 'Type' }),
        cell: renderTypeCell,
    },
    {
        accessorKey: 'category',
        ...createSortingHeader({
            key: 'column.category',
            fallback: 'Category',
        }),
        cell: renderCategoryCell,
    },
    createCurrencyColumn<Product>({
        accessorKey: 'cost',
        label: { key: 'column.cost', fallback: 'Cost' },
        locale: FORMAT_LOCALE,
    }),
    createCurrencyColumn<Product>({
        accessorKey: 'selling_price',
        label: { key: 'column.price', fallback: 'Price' },
        locale: FORMAT_LOCALE,
    }),
    {
        accessorKey: 'status',
        ...createSortingHeader({ key: 'column.status', fallback: 'Status' }),
        cell: renderStatusCell,
    },
    createActionsColumn<Product>(),
];
