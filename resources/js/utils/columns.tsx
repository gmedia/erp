'use client';

import { ColumnDef } from '@tanstack/react-table';
import { formatDate } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { ArrowUpDown } from 'lucide-react';
import { GenericActions } from '@/components/common/ActionsDropdown';

// Type definitions for better type safety
export type ColumnBuilderOptions<T> = {
  accessorKey: keyof T;
  label: string;
  enableSorting?: boolean;
  className?: string;
};

export type DateColumnOptions<T> = ColumnBuilderOptions<T> & {
  dateFormat?: string;
  locale?: string;
};

export type CurrencyColumnOptions<T> = ColumnBuilderOptions<T> & {
  currency?: string;
  locale?: string;
  minimumFractionDigits?: number;
  maximumFractionDigits?: number;
};

export type NumberColumnOptions<T> = ColumnBuilderOptions<T> & {
  locale?: string;
  minimumFractionDigits?: number;
  maximumFractionDigits?: number;
};

export type LinkColumnOptions<T> = ColumnBuilderOptions<T> & {
  linkType: 'email' | 'phone' | 'url';
  linkClassName?: string;
};

export type ActionsColumnOptions<T> = {
  onEdit?: (row: T) => void;
  onDelete?: (row: T) => void;
  onView?: (row: T) => void;
  enableHiding?: boolean;
};

// Helper function to create sorting header
export function createSortingHeader<T>(label: string) {
  return {
    header: ({ column }: { column: { toggleSorting: (value: boolean) => void; getIsSorted: () => 'asc' | 'desc' | false } }) => (
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
export function createTextColumn<T extends Record<string, any>>(
  options: ColumnBuilderOptions<T>
): ColumnDef<T> {
  const { accessorKey, label, enableSorting = true } = options;

  const baseColumn: ColumnDef<T> = {
    accessorKey: accessorKey as string,
  };

  if (enableSorting) {
    return {
      ...baseColumn,
      ...createSortingHeader<T>(label),
    };
  }

  return {
    ...baseColumn,
    header: label,
  };
}

// Date column with formatting
export function createDateColumn<T extends Record<string, any>>(
  options: DateColumnOptions<T>
): ColumnDef<T> {
  const {
    accessorKey,
    label,
    enableSorting = true,
    dateFormat,
    locale = 'en-US',
  } = options;

  const baseColumn: ColumnDef<T> = {
    accessorKey: accessorKey as string,
    cell: ({ row }) => {
      const value = row.getValue(accessorKey as string);
      if (!value) return <div>-</div>;
      return <div>{formatDate(value as string)}</div>;
    },
  };

  if (enableSorting) {
    return {
      ...baseColumn,
      ...createSortingHeader<T>(label),
    };
  }

  return {
    ...baseColumn,
    header: label,
  };
}

// Currency column with formatting
export function createCurrencyColumn<T extends Record<string, any>>(
  options: CurrencyColumnOptions<T>
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
      const value = parseFloat(row.getValue(accessorKey as string) as string);
      if (isNaN(value)) {
        return <div className={className}>-</div>;
      }

      const formatted = new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
        minimumFractionDigits,
        maximumFractionDigits,
      }).format(value);

      return <div className={className}>{formatted}</div>;
    },
  };

  if (enableSorting) {
    return {
      ...baseColumn,
      ...createSortingHeader<T>(label),
    };
  }

  return {
    ...baseColumn,
    header: label,
  };
}

// Number column with formatting
export function createNumberColumn<T extends Record<string, any>>(
  options: NumberColumnOptions<T>
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
      const value = parseFloat(row.getValue(accessorKey as string) as string);
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
      ...createSortingHeader<T>(label),
    };
  }

  return {
    ...baseColumn,
    header: label,
  };
}

// Link column for email, phone, or URL
export function createLinkColumn<T extends Record<string, any>>(
  options: LinkColumnOptions<T>
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
      ...createSortingHeader<T>(label),
    };
  }

  return {
    ...baseColumn,
    header: label,
  };
}

// Email column (convenience wrapper for createLinkColumn)
export function createEmailColumn<T extends Record<string, any>>(
  options: Omit<ColumnBuilderOptions<T>, 'accessorKey'> & { accessorKey: keyof T }
): ColumnDef<T> {
  return createLinkColumn<T>({
    ...options,
    linkType: 'email',
  });
}

// Phone column (convenience wrapper for createLinkColumn)
export function createPhoneColumn<T extends Record<string, any>>(
  options: Omit<ColumnBuilderOptions<T>, 'accessorKey'> & { accessorKey: keyof T }
): ColumnDef<T> {
  return createLinkColumn<T>({
    ...options,
    linkType: 'phone',
  });
}

// URL column (convenience wrapper for createLinkColumn)
export function createUrlColumn<T extends Record<string, any>>(
  options: Omit<ColumnBuilderOptions<T>, 'accessorKey'> & { accessorKey: keyof T }
): ColumnDef<T> {
  return createLinkColumn<T>({
    ...options,
    linkType: 'url',
  });
}

// Actions column for edit/delete/view operations
export function createActionsColumn<T extends Record<string, any>>(
  options: ActionsColumnOptions<T> = {}
): ColumnDef<T> {
  const {
    onEdit,
    onDelete,
    onView,
    enableHiding = false,
  } = options;

  return {
    id: 'actions',
    enableHiding,
    cell: ({ row }) => {
      const item = row.original as T;
      return (
        <GenericActions
          item={item}
          onView={onView}
          onEdit={onEdit ? () => onEdit(item) : undefined}
          onDelete={onDelete ? () => onDelete(item) : undefined}
        />
      );
    },
  };
}

// Select column for row selection (checkbox)
export function createSelectColumn<T extends Record<string, any>>(): ColumnDef<T> {
  return {
    id: 'select',
    header: ({ table }) => (
      <Checkbox
        checked={
          table.getIsAllPageRowsSelected() ||
          (table.getIsSomePageRowsSelected() && 'indeterminate')
        }
        onCheckedChange={(value) =>
          table.toggleAllPageRowsSelected(!!value)
        }
        aria-label="Select all"
      />
    ),
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
export function createBadgeColumn<T extends Record<string, any>>(
  options: ColumnBuilderOptions<T> & {
    colorMap?: Record<string, string>;
    defaultColor?: string;
  }
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
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colorClass}`}>
          {value}
        </span>
      );
    },
  };

  if (enableSorting) {
    return {
      ...baseColumn,
      ...createSortingHeader<T>(label),
    };
  }

  return {
    ...baseColumn,
    header: label,
  };
}

// Simple text column without sorting
export function createSimpleTextColumn<T extends Record<string, any>>(
  options: ColumnBuilderOptions<T>
): ColumnDef<T> {
  const { accessorKey, label } = options;

  return {
    accessorKey: accessorKey as string,
    header: label,
  };
}

// Simple entity columns for basic CRUD entities (departments, positions)
export function createSimpleEntityColumns<T extends Record<string, any>>(): ColumnDef<T>[] {
  return [
    createSelectColumn<T>(),
    createTextColumn<T>({ accessorKey: 'name' as keyof T, label: 'Name' }),
    createDateColumn<T>({ accessorKey: 'created_at' as keyof T, label: 'Created At' }),
    createDateColumn<T>({ accessorKey: 'updated_at' as keyof T, label: 'Updated At' }),
    createActionsColumn<T>(),
  ];
}
