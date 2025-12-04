'use client';

import { createSortingHeader } from '@/components/common/BaseColumns';
import { formatDate } from '@/lib/utils';
import { ColumnDef } from '@tanstack/react-table';

/**
 * Creates a date column with sorting header and consistent date formatting
 * @param accessorKey - The data field to access
 * @param label - The column header label
 * @returns ColumnDef with date formatting and sorting
 */
export function createDateColumn<T extends Record<string, any>>(
    accessorKey: string,
    label: string
): ColumnDef<T> {
    return {
        accessorKey,
        ...createSortingHeader<T>(label),
        cell: ({ row }) => (
            <div>{formatDate(row.getValue(accessorKey))}</div>
        ),
    };
}

/**
 * Creates a currency column with sorting header and USD formatting
 * @param accessorKey - The data field to access
 * @param label - The column header label
 * @param options - Optional configuration for currency formatting
 * @returns ColumnDef with currency formatting and sorting
 */
export function createCurrencyColumn<T extends Record<string, any>>(
    accessorKey: string,
    label: string,
    options: {
        currency?: string;
        locale?: string;
        className?: string;
    } = {}
): ColumnDef<T> {
    const {
        currency = 'USD',
        locale = 'en-US',
        className = 'font-medium',
    } = options;

    return {
        accessorKey,
        ...createSortingHeader<T>(label),
        cell: ({ row }) => {
            const value = parseFloat(row.getValue(accessorKey));
            if (isNaN(value)) {
                return <div className={className}>$0.00</div>;
            }
            
            const formatted = new Intl.NumberFormat(locale, {
                style: 'currency',
                currency,
            }).format(value);
            
            return <div className={className}>{formatted}</div>;
        },
    };
}

/**
 * Creates a basic text column with sorting header
 * @param accessorKey - The data field to access
 * @param label - The column header label
 * @returns ColumnDef with text formatting and sorting
 */
export function createTextColumn<T extends Record<string, any>>(
    accessorKey: string,
    label: string
): ColumnDef<T> {
    return {
        accessorKey,
        ...createSortingHeader<T>(label),
    };
}

/**
 * Creates a simple text column without sorting
 * @param accessorKey - The data field to access
 * @param label - The column header label
 * @returns ColumnDef with text formatting
 */
export function createSimpleTextColumn<T extends Record<string, any>>(
    accessorKey: string,
    label: string
): ColumnDef<T> {
    return {
        accessorKey,
        header: label,
    };
}

/**
 * Creates a number column with sorting header and optional formatting
 * @param accessorKey - The data field to access
 * @param label - The column header label
 * @param options - Optional configuration for number formatting
 * @returns ColumnDef with number formatting and sorting
 */
export function createNumberColumn<T extends Record<string, any>>(
    accessorKey: string,
    label: string,
    options: {
        locale?: string;
        minimumFractionDigits?: number;
        maximumFractionDigits?: number;
        className?: string;
    } = {}
): ColumnDef<T> {
    const {
        locale = 'en-US',
        minimumFractionDigits = 0,
        maximumFractionDigits = 2,
        className = 'text-right',
    } = options;

    return {
        accessorKey,
        ...createSortingHeader<T>(label),
        cell: ({ row }) => {
            const value = parseFloat(row.getValue(accessorKey));
            if (isNaN(value)) {
                return <div className={className}>0</div>;
            }
            
            const formatted = new Intl.NumberFormat(locale, {
                minimumFractionDigits,
                maximumFractionDigits,
            }).format(value);
            
            return <div className={className}>{formatted}</div>;
        },
    };
}

/**
 * Creates an email column with mailto link
 * @param accessorKey - The data field to access
 * @param label - The column header label
 * @returns ColumnDef with email formatting and sorting
 */
export function createEmailColumn<T extends Record<string, any>>(
    accessorKey: string,
    label: string
): ColumnDef<T> {
    return {
        accessorKey,
        ...createSortingHeader<T>(label),
        cell: ({ row }) => {
            const email = row.getValue(accessorKey) as string;
            return (
                <a
                    href={`mailto:${email}`}
                    className="text-blue-600 hover:text-blue-800 hover:underline"
                >
                    {email}
                </a>
            );
        },
    };
}

/**
 * Creates a phone column with tel link
 * @param accessorKey - The data field to access
 * @param label - The column header label
 * @returns ColumnDef with phone formatting
 */
export function createPhoneColumn<T extends Record<string, any>>(
    accessorKey: string,
    label: string
): ColumnDef<T> {
    return {
        accessorKey,
        header: label,
        cell: ({ row }) => {
            const phone = row.getValue(accessorKey) as string;
            return (
                <a
                    href={`tel:${phone}`}
                    className="text-blue-600 hover:text-blue-800 hover:underline"
                >
                    {phone}
                </a>
            );
        },
    };
}