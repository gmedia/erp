import { type Key, type ReactNode } from 'react';

export interface ViewModalItemsTableColumn<TItem> {
    readonly key: string;
    readonly header: string;
    readonly align?: 'left' | 'right';
    readonly render: (item: TItem) => ReactNode;
}

interface ViewModalItemsTableProps<TItem> {
    readonly items: readonly TItem[] | null | undefined;
    readonly columns: readonly ViewModalItemsTableColumn<TItem>[];
    readonly minWidthClassName: string;
    readonly title?: string;
    readonly getRowKey?: (item: TItem, index: number) => Key;
}

function getHeaderClassName(align: 'left' | 'right' = 'left'): string {
    return align === 'right' ? 'p-2 text-right' : 'p-2 text-left';
}

function getCellClassName(align: 'left' | 'right' = 'left'): string {
    return align === 'right' ? 'p-2 text-right' : 'p-2';
}

export function ViewModalItemsTable<TItem>({
    items,
    columns,
    minWidthClassName,
    title = 'Items',
    getRowKey,
}: Readonly<ViewModalItemsTableProps<TItem>>) {
    const rows = items ?? [];

    return (
        <div className="space-y-2">
            <h4 className="text-sm font-semibold">{title}</h4>
            <div className="overflow-x-auto rounded-md border">
                <table className={`${minWidthClassName} text-sm`}>
                    <thead>
                        <tr className="border-b">
                            {columns.map((column) => (
                                <th
                                    key={column.key}
                                    className={getHeaderClassName(column.align)}
                                >
                                    {column.header}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((item, index) => (
                            <tr
                                key={getRowKey?.(item, index) ?? index}
                                className="border-b last:border-b-0"
                            >
                                {columns.map((column) => (
                                    <td
                                        key={column.key}
                                        className={getCellClassName(column.align)}
                                    >
                                        {column.render(item)}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
