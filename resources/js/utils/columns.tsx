'use client';

import { GenericActions } from '@/components/common/ActionsDropdown';
import {
    labelFallbackText,
    LabelText,
    type TranslatableLabel,
} from '@/components/common/TranslatedText';
import { Badge, type BadgeVariant } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    formatStatusLabel,
    priorityBadgeVariant,
    statusBadgeVariant,
} from '@/lib/status-badge';
import { cn, formatDate } from '@/lib/utils';
import { FORMAT_LOCALE } from '@/utils/formatters';
import {
    formatCurrencyByRegionalSettings,
    formatNumberByRegionalSettings,
} from '@/utils/number-format';
import { ColumnDef } from '@tanstack/react-table';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-react';
import type { ReactNode } from 'react';

function SortDirectionIcon({
    sorted,
    side = 'right',
}: {
    sorted: 'asc' | 'desc' | false;
    side?: 'left' | 'right';
}) {
    const spacing = side === 'left' ? 'mr-2' : 'ml-2';
    if (sorted === 'asc') {
        return (
            <ArrowUp
                className={`${spacing} h-4 w-4 shrink-0 text-foreground`}
            />
        );
    }
    if (sorted === 'desc') {
        return (
            <ArrowDown
                className={`${spacing} h-4 w-4 shrink-0 text-foreground`}
            />
        );
    }
    return (
        <ArrowUpDown
            className={`${spacing} h-4 w-4 shrink-0 text-muted-foreground`}
        />
    );
}

// Type definitions for better type safety
export type ColumnBuilderOptions<T = Record<string, unknown>> = {
    accessorKey: keyof T;
    label: TranslatableLabel;
    enableSorting?: boolean;
    className?: string;
};

export interface CustomTableMeta<T> {
    onView?: (item: T) => void;
    onEdit?: (item: T) => void;
    onDelete?: (item: T) => void;
    viewPath?: (item: T) => string;
    [key: string]: unknown;
}

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
    /** Sembunyikan Edit bila predikat false (mis. hanya status draft). */
    canEdit?: (row: T) => boolean;
    /** Sembunyikan Delete bila predikat false. */
    canDelete?: (row: T) => boolean;
};

// Helper function to create sorting header
export function createSortingHeader(
    label: TranslatableLabel,
    options: { align?: 'left' | 'right' } = {},
) {
    const isRight = options.align === 'right';
    const testIdSource = labelFallbackText(label);
    return {
        header: ({
            column,
        }: {
            column: {
                toggleSorting: (value: boolean) => void;
                getIsSorted: () => 'asc' | 'desc' | false;
            };
        }) => (
            <div className={cn(isRight && 'flex justify-end')}>
                <Button
                    variant="ghost"
                    className={cn(
                        isRight
                            ? 'ml-auto h-8 max-w-full justify-end px-0 has-[>svg]:px-0'
                            : '-ml-2 h-8 px-2',
                    )}
                    onClick={() =>
                        column.toggleSorting(column.getIsSorted() === 'asc')
                    }
                    data-testid={`sort-${testIdSource.toLowerCase().replaceAll(/\s+/g, '-')}`}
                    data-sort-state={column.getIsSorted() || 'none'}
                >
                    {isRight ? (
                        <SortDirectionIcon
                            sorted={column.getIsSorted()}
                            side="left"
                        />
                    ) : null}
                    <span
                        className={cn(
                            'max-w-full min-w-0',
                            isRight
                                ? 'text-right whitespace-nowrap'
                                : 'line-clamp-2 text-left whitespace-normal',
                        )}
                    >
                        <LabelText label={label} />
                    </span>
                    {isRight ? null : (
                        <SortDirectionIcon sorted={column.getIsSorted()} />
                    )}
                </Button>
            </div>
        ),
    };
}

// Basic text column with optional sorting
export function createTextColumn<T = Record<string, unknown>>(
    options: ColumnBuilderOptions<T> & {
        /** Sel utama baris (nama/kode dokumen): teks tebal (§0.4). */
        primary?: boolean;
        /** Baris meta kecil di bawah teks utama (sel dua baris, §0.4). */
        description?: (row: T) => ReactNode;
    },
): ColumnDef<T> {
    const {
        accessorKey,
        label,
        enableSorting = true,
        primary = false,
        description,
    } = options;

    const baseColumn: ColumnDef<T> = {
        accessorKey: accessorKey as string,
        cell: ({ row }) => {
            const value = row.getValue(accessorKey as string);
            const text =
                typeof value === 'string' || typeof value === 'number'
                    ? String(value)
                    : '-';
            const meta = description?.(row.original);

            if (!primary && !meta) {
                return (
                    <div className="max-w-56 truncate" title={text}>
                        {text}
                    </div>
                );
            }

            return (
                <div className="max-w-56 min-w-0">
                    <div
                        className={cn(
                            'truncate',
                            primary && 'font-medium text-foreground',
                        )}
                        title={text}
                    >
                        {text}
                    </div>
                    {meta ? (
                        <div className="truncate text-xs text-muted-foreground">
                            {meta}
                        </div>
                    ) : null}
                </div>
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
        header: () => <LabelText label={label} />,
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

            if (
                typeof value === 'string' ||
                typeof value === 'number' ||
                value instanceof Date
            ) {
                return <div>{formatDate(String(value))}</div>;
            }

            return <div>-</div>;
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
        header: () => <LabelText label={label} />,
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
        currency,
        locale = FORMAT_LOCALE,
        minimumFractionDigits = 0,
        maximumFractionDigits = 2,
        className = 'text-right font-medium tabular-nums',
    } = options;

    const baseColumn: ColumnDef<T> = {
        accessorKey: accessorKey as string,
        cell: ({ row }) => {
            const value = row.getValue(accessorKey as string);
            const numValue =
                typeof value === 'number'
                    ? value
                    : Number.parseFloat(String(value));
            if (Number.isNaN(numValue)) {
                return <div className={className}>-</div>;
            }

            const formatted = formatCurrencyByRegionalSettings(numValue, {
                locale,
                currency,
                minimumFractionDigits,
                maximumFractionDigits,
            });

            return <div className={className}>{formatted}</div>;
        },
    };

    if (enableSorting) {
        return {
            ...baseColumn,
            ...createSortingHeader(label, { align: 'right' }),
        };
    }

    return {
        ...baseColumn,
        enableSorting: false,
        header: () => (
            <div className="text-right">
                <LabelText label={label} />
            </div>
        ),
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
        meta: { numeric: true },
        cell: ({ row }) => {
            const value = Number.parseFloat(
                row.getValue(accessorKey as string) as string,
            );
            if (Number.isNaN(value)) {
                return <div className={className}>-</div>;
            }

            const formatted = formatNumberByRegionalSettings(value, {
                locale,
                minimumFractionDigits,
                maximumFractionDigits,
            });

            return <div className={className}>{formatted}</div>;
        },
    };

    if (enableSorting) {
        return {
            ...baseColumn,
            ...createSortingHeader(label, { align: 'right' }),
        };
    }

    return {
        ...baseColumn,
        enableSorting: false,
        header: () => (
            <div className="text-right">
                <LabelText label={label} />
            </div>
        ),
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
        // Email/telepon sebagai teks biasa agar tabel tidak ramai tautan biru;
        // tetap dapat diklik (W4-12).
        linkClassName = 'text-foreground underline-offset-4 hover:underline',
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
        header: () => <LabelText label={label} />,
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
    const {
        onEdit,
        onDelete,
        onView,
        viewPath,
        enableHiding = false,
        canEdit,
        canDelete,
    } = options;

    return {
        id: 'actions',
        enableHiding,
        size: 56,
        meta: {
            viewPath,
        },
        cell: ({ row }) => {
            const item = row.original;
            return (
                <GenericActions
                    item={item}
                    viewUrl={viewPath ? viewPath(item) : undefined}
                    onView={onView}
                    onEdit={
                        onEdit && (canEdit?.(item) ?? true)
                            ? () => onEdit(item)
                            : undefined
                    }
                    onDelete={
                        onDelete && (canDelete?.(item) ?? true)
                            ? () => onDelete(item)
                            : undefined
                    }
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
        header: ({ table }) => (
            <Checkbox
                checked={table.getIsAllPageRowsSelected()}
                onCheckedChange={(value) =>
                    table.toggleAllPageRowsSelected(!!value)
                }
                aria-label="Select all"
                data-testid="select-all"
            />
        ),
        cell: ({ row }) => (
            <Checkbox
                checked={row.getIsSelected()}
                onCheckedChange={(value) => row.toggleSelected(!!value)}
                aria-label="Select row"
                data-testid="select-row"
            />
        ),
        enableSorting: false,
        enableHiding: false,
    };
}

// Badge column for status-like values.
// Default render memakai satu peta status→tone terpusat (lib/status-badge.ts,
// W4-01/W4-02) + shadcn Badge. `colorMap`/`defaultColor` (klas, string) masih
// didukung agar pemakai lama tidak pecah, tetapi pemakai baru sebaiknya
// memakai `variantMap` atau helper `createStatusBadgeColumn`.
export function createBadgeColumn<T = Record<string, unknown>>(
    options: ColumnBuilderOptions<T> & {
        colorMap?: Record<string, string>;
        defaultColor?: string;
        /** Peta nilai → variant Badge (menggantikan colorMap class string). */
        variantMap?: Record<string, BadgeVariant>;
        /** Override tone default (bila status tidak dikenal). */
        defaultVariant?: BadgeVariant;
        /** Humanize label (snake_case → Title Case). Default: true. */
        humanize?: boolean;
    },
): ColumnDef<T> {
    const {
        accessorKey,
        label,
        enableSorting = true,
        colorMap = {},
        defaultColor,
        variantMap,
        defaultVariant = 'outline',
        humanize = true,
    } = options;

    const baseColumn: ColumnDef<T> = {
        accessorKey: accessorKey as string,
        cell: ({ row }) => {
            const value = row.getValue(accessorKey as string) as string;
            if (!value) return <div>-</div>;

            const text = humanize ? formatStatusLabel(value) : value;

            const variant = variantMap?.[value] ?? statusBadgeVariant(value);

            // Fallback legacy: pemakai lama yang masih melewati colorMap.
            if (colorMap[value]) {
                return (
                    <span
                        className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${colorMap[value]}`}
                    >
                        {text}
                    </span>
                );
            }
            if (defaultColor && !variantMap) {
                return (
                    <span
                        className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${defaultColor}`}
                    >
                        {text}
                    </span>
                );
            }

            return <Badge variant={variant ?? defaultVariant}>{text}</Badge>;
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
        header: () => <LabelText label={label} />,
    };
}

// Status badge column (satu peta status→tone terpusat, W4-01/W4-02).
export function createStatusBadgeColumn<T = Record<string, unknown>>(
    options: ColumnBuilderOptions<T> & {
        variantMap?: Record<string, BadgeVariant>;
        defaultVariant?: BadgeVariant;
        humanize?: boolean;
    },
): ColumnDef<T> {
    const { accessorKey, label, variantMap, defaultVariant, humanize } =
        options;
    return createBadgeColumn<T>({
        accessorKey,
        label,
        enableSorting: options.enableSorting,
        variantMap,
        defaultVariant,
        humanize,
    });
}

// Adjustment type badge column (damage/expired/..., W4-01).
export function createAdjustmentTypeBadgeColumn<T = Record<string, unknown>>(
    options: ColumnBuilderOptions<T>,
): ColumnDef<T> {
    const { accessorKey, label, enableSorting = true } = options;

    const baseColumn: ColumnDef<T> = {
        accessorKey: accessorKey as string,
        cell: ({ row }) => {
            const value = row.getValue(accessorKey as string) as string;
            if (!value) return <div>-</div>;
            return <Badge variant="outline">{formatStatusLabel(value)}</Badge>;
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
        header: () => <LabelText label={label} />,
    };
}

// Priority badge column (urgent/high/normal/low, W4-02).
export function createPriorityBadgeColumn<T = Record<string, unknown>>(
    options: ColumnBuilderOptions<T>,
): ColumnDef<T> {
    const { accessorKey, label, enableSorting = true } = options;

    const baseColumn: ColumnDef<T> = {
        accessorKey: accessorKey as string,
        cell: ({ row }) => {
            const value = row.getValue(accessorKey as string) as string;
            if (!value) return <div>-</div>;
            return (
                <Badge variant={priorityBadgeVariant(value)}>
                    {formatStatusLabel(value)}
                </Badge>
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
        header: () => <LabelText label={label} />,
    };
}

// Simple text column without sorting (alias for createTextColumn with enableSorting: false)
export const createSimpleTextColumn = <T = Record<string, unknown>,>(
    options: Omit<ColumnBuilderOptions<T>, 'enableSorting'>,
): ColumnDef<T> => createTextColumn<T>({ ...options, enableSorting: false });

export function createRowCurrencyAmountColumn<
    T extends { currency?: string | null },
>(options: {
    accessorKey: keyof T;
    label: TranslatableLabel;
    className?: string;
}): ColumnDef<T> {
    const { accessorKey, label, className = 'text-right' } = options;

    return {
        accessorKey: accessorKey as string,
        meta: { numeric: true },
        ...createSortingHeader(label, { align: 'right' }),
        cell: ({ row }) => (
            <div className={className}>
                {formatCurrencyByRegionalSettings(
                    row.getValue(accessorKey as string) as string | number,
                    {
                        locale: FORMAT_LOCALE,
                        currency: row.original.currency || undefined,
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 2,
                    },
                )}
            </div>
        ),
    };
}

// Simple entity columns for basic CRUD entities (departments, positions)
export function createSimpleEntityColumns<
    T extends { name: string; created_at: string; updated_at: string },
>(): ColumnDef<T>[] {
    return [
        createSelectColumn<T>(),
        createTextColumn<T>({
            accessorKey: 'name',
            label: 'Name',
            primary: true,
        }),
        createDateColumn<T>({ accessorKey: 'created_at', label: 'Created At' }),
        createDateColumn<T>({ accessorKey: 'updated_at', label: 'Updated At' }),
        createActionsColumn<T>(),
    ];
}
