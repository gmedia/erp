'use client';

import { GenericActions } from '@/components/common/ActionsDropdown';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { formatDate } from '@/lib/utils';
import { ColumnDef } from '@tanstack/react-table';
import { ArrowUpDown } from 'lucide-react';

// Type definitions for better type safety
export type ColumnBuilderOptions<T = Record<string, unknown>> = {
    accessorKey: keyof T;
    label: string;
    enableSorting?: boolean;
    className?: string;
};

export type DateColumnOptions<T = Record<string, unknown>> =
    ColumnBuilderOptions<T> & {
        dateFormat?: string;
        locale?: string;
    };

export type CurrencyColumnOptions<T = Record<string, unknown>> =
    ColumnBuilderOptions<T> & {
        currency?: string;
        locale?: string;
        minimumFractionDigits?: number;
        maximumFractionDigits?: number;
    };

export type NumberColumnOptions<T = Record<string, unknown>> =
    ColumnBuilderOptions<T> & {
        locale?: string;
        minimumFractionDigits?: number;
        maximumFractionDigits?: number;
    };

export type LinkColumnOptions<T = Record<string, unknown>> =
    ColumnBuilderOptions<T> & {
        linkType: 'email' | 'phone' | 'url';
        linkClassName?: string;
    };

export type ActionsColumnOptions<T = Record<string, unknown>> = {
    onEdit?: (row: T) => void;
    onDelete?: (row: T) => void;
    onView?: (row: T) => void;
    viewPath?: (row: T) => string;
    enableHiding?: boolean;
};

// Helper function to create sorting header
export function createSortingHeader(label: string) {
    return {
        header: ({
            column,
        }: {
            column: {
                toggleSorting: (value: boolean) => void;
                getIsSorted: () => 'asc' | 'desc' | false;
            };
        }) => (
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

// Basic text column with optional sorting
export function createTextColumn<T = Record<string, unknown>>(
    options: ColumnBuilderOptions<T>,
): ColumnDef<T> {
    const { accessorKey, label, enableSorting = true } = options;

    const baseColumn: ColumnDef<T> = {
        accessorKey: accessorKey as string,
        cell: ({ row }) => {
            const value = row.getValue(accessorKey as string);
            return <div>{value ? String(value) : '-'}</div>;
        },
    };

    if (enableSorting) {
        return {
            ...baseColumn,
            ...createSortingHeader(label),
        };
    }

    return {
        ...baseColumn,
        enableSorting: false,
        header: label,
    };
}

// Date column with formatting
export function createDateColumn<T = Record<string, unknown>>(
    options: DateColumnOptions<T>,
): ColumnDef<T> {
    const { accessorKey, label, enableSorting = true } = options;

    const baseColumn: ColumnDef<T> = {
        accessorKey: accessorKey as string,
        cell: ({ row }) => {
            const value = row.getValue(accessorKey as string);
            if (!value) return <div>-</div>;
            return <div>{formatDate(String(value))}</div>;
        },
    };

    if (enableSorting) {
        return {
            ...baseColumn,
            ...createSortingHeader(label),
        };
    }

    return {
        ...baseColumn,
        enableSorting: false,
        header: label,
    };
}

// Currency column with formatting
export function createCurrencyColumn<T = Record<string, unknown>>(
    options: CurrencyColumnOptions<T>,
): ColumnDef<T> {
    const {
        accessorKey,
        label,
        enableSorting = true,
        currency = 'USD',
        locale = 'en-US',
        minimumFractionDigits = 2,
        maximumFractionDigits = 2,
        className = 'font-medium',
    } = options;

    const baseColumn: ColumnDef<T> = {
        accessorKey: accessorKey as string,
        cell: ({ row }) => {
            const value = row.getValue(accessorKey as string);
            const numValue =
                typeof value === 'number' ? value : parseFloat(String(value));
            if (isNaN(numValue)) {
                return <div className={className}>-</div>;
            }

            const formatted = new Intl.NumberFormat(locale, {
                style: 'currency',
                currency,
                minimumFractionDigits,
                maximumFractionDigits,
            }).format(numValue);

            return <div className={className}>{formatted}</div>;
        },
    };

    if (enableSorting) {
        return {
            ...baseColumn,
            ...createSortingHeader(label),
        };
    }

    return {
        ...baseColumn,
        enableSorting: false,
        header: label,
    };
}

// Number column with formatting
export function createNumberColumn<T = Record<string, unknown>>(
    options: NumberColumnOptions<T>,
): ColumnDef<T> {
    const {
        accessorKey,
        label,
        enableSorting = true,
        locale = 'en-US',
        minimumFractionDigits = 0,
        maximumFractionDigits = 2,
        className = 'text-right',
    } = options;

    const baseColumn: ColumnDef<T> = {
        accessorKey: accessorKey as string,
        cell: ({ row }) => {
            const value = parseFloat(
                row.getValue(accessorKey as string) as string,
            );
            if (isNaN(value)) {
                return <div className={className}>-</div>;
            }

            const formatted = new Intl.NumberFormat(locale, {
                minimumFractionDigits,
                maximumFractionDigits,
            }).format(value);

            return <div className={className}>{formatted}</div>;
        },
    };

    if (enableSorting) {
        return {
            ...baseColumn,
            ...createSortingHeader(label),
        };
    }

    return {
        ...baseColumn,
        enableSorting: false,
        header: label,
    };
}

// Link column for email, phone, or URL
export function createLinkColumn<T = Record<string, unknown>>(
    options: LinkColumnOptions<T>,
): ColumnDef<T> {
    const {
        accessorKey,
        label,
        enableSorting = true,
        linkType,
        linkClassName = 'text-blue-600 hover:text-blue-800 hover:underline',
    } = options;

    const getHref = (value: string) => {
        switch (linkType) {
            case 'email':
                return `mailto:${value}`;
            case 'phone':
                return `tel:${value}`;
            case 'url':
                return value.startsWith('http') ? value : `https://${value}`;
            default:
                return '#';
        }
    };

    const baseColumn: ColumnDef<T> = {
        accessorKey: accessorKey as string,
        cell: ({ row }) => {
            const value = row.getValue(accessorKey as string) as string;
            if (!value) return <div>-</div>;

            return (
                <a href={getHref(value)} className={linkClassName}>
                    {value}
                </a>
            );
        },
    };

    if (enableSorting) {
        return {
            ...baseColumn,
            ...createSortingHeader(label),
        };
    }

    return {
        ...baseColumn,
        enableSorting: false,
        header: label,
    };
}

// Email column (convenience wrapper for createLinkColumn)
export function createEmailColumn<T = Record<string, unknown>>(
    options: Omit<ColumnBuilderOptions<T>, 'accessorKey'> & {
        accessorKey: keyof T;
    },
): ColumnDef<T> {
    return createLinkColumn<T>({
        ...options,
        linkType: 'email',
    });
}

// Phone column (convenience wrapper for createLinkColumn)
export function createPhoneColumn<T = Record<string, unknown>>(
    options: Omit<ColumnBuilderOptions<T>, 'accessorKey'> & {
        accessorKey: keyof T;
    },
): ColumnDef<T> {
    return createLinkColumn<T>({
        ...options,
        linkType: 'phone',
    });
}

// URL column (convenience wrapper for createLinkColumn)
export function createUrlColumn<T extends Record<string, unknown>>(
    options: Omit<ColumnBuilderOptions<T>, 'accessorKey'> & {
        accessorKey: keyof T;
    },
): ColumnDef<T> {
    return createLinkColumn<T>({
        ...options,
        linkType: 'url',
    });
}

// Actions column for edit/delete/view operations
export function createActionsColumn<T = Record<string, unknown>>(
    options: ActionsColumnOptions<T> = {},
): ColumnDef<T> {
    const { onEdit, onDelete, onView, viewPath, enableHiding = false } = options;

    return {
        id: 'actions',
        enableHiding,
        meta: {
            viewPath,
        },
        cell: ({ row }) => {
            const item = row.original as T;
            return (
                <GenericActions
                    item={item}
                    viewUrl={viewPath ? viewPath(item) : undefined}
                    onView={onView}
                    onEdit={onEdit ? () => onEdit(item) : undefined}
                    onDelete={onDelete ? () => onDelete(item) : undefined}
                />
            );
        },
    };
}

// Select column for row selection (checkbox)
// Note: Header checkbox removed because it's misleading with remote data source
// (only selects current page, not all data across all pages)
export function createSelectColumn<
    T = Record<string, unknown>,
>(): ColumnDef<T> {
    return {
        id: 'select',
        header: () => null,
        cell: ({ row }) => (
            <Checkbox
                checked={row.getIsSelected()}
                onCheckedChange={(value) => row.toggleSelected(!!value)}
                aria-label="Select row"
            />
        ),
        enableSorting: false,
        enableHiding: false,
    };
}

// Badge column for status-like values
export function createBadgeColumn<T = Record<string, unknown>>(
    options: ColumnBuilderOptions<T> & {
        colorMap?: Record<string, string>;
        defaultColor?: string;
    },
): ColumnDef<T> {
    const {
        accessorKey,
        label,
        enableSorting = true,
        colorMap = {},
        defaultColor = 'bg-gray-100 text-gray-800',
    } = options;

    const baseColumn: ColumnDef<T> = {
        accessorKey: accessorKey as string,
        cell: ({ row }) => {
            const value = row.getValue(accessorKey as string) as string;
            if (!value) return <div>-</div>;

            const colorClass = colorMap[value] || defaultColor;

            return (
                <span
                    className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${colorClass}`}
                >
                    {value}
                </span>
            );
        },
    };

    if (enableSorting) {
        return {
            ...baseColumn,
            ...createSortingHeader(label),
        };
    }

    return {
        ...baseColumn,
        enableSorting: false,
        header: label,
    };
}

// Simple text column without sorting (alias for createTextColumn with enableSorting: false)
export const createSimpleTextColumn = <T = Record<string, unknown>,>(
    options: Omit<ColumnBuilderOptions<T>, 'enableSorting'>,
): ColumnDef<T> => createTextColumn<T>({ ...options, enableSorting: false });

// Simple entity columns for basic CRUD entities (departments, positions)
export function createSimpleEntityColumns<
    T extends { name: string; created_at: string; updated_at: string },
>(): ColumnDef<T>[] {
    return [
        createSelectColumn<T>(),
        createTextColumn<T>({ accessorKey: 'name', label: 'Name' }),
        createDateColumn<T>({ accessorKey: 'created_at', label: 'Created At' }),
        createDateColumn<T>({ accessorKey: 'updated_at', label: 'Updated At' }),
        createActionsColumn<T>(),
    ];
}
