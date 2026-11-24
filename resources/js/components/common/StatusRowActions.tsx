'use client';

import { GenericActions } from '@/components/common/ActionsDropdown';
import {
    type StatusActionConfig,
    StatusActionDialog,
} from '@/components/common/StatusActionDialog';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { useState } from 'react';

export interface RowActionHandlers<T> {
    item: T;
    onView?: (item: T) => void;
    onEdit?: (item: T) => void;
    onDelete?: (item: T) => void;
}

interface StatusRowActionsProps<
    T,
    A extends string,
> extends RowActionHandlers<T> {
    actions: StatusActionConfig<A>[];
    processing: boolean;
    processingLabel: string;
    run: (item: T, action: A) => Promise<boolean>;
}

/** Menu baris standar ditambah aksi status dengan dialog konfirmasi. */
export function StatusRowActions<
    T extends { id: number | string },
    A extends string,
>({
    item,
    onView,
    onEdit,
    onDelete,
    actions,
    processing,
    processingLabel,
    run,
}: Readonly<StatusRowActionsProps<T, A>>) {
    const [pending, setPending] = useState<A | null>(null);
    const config = actions.find(({ action }) => action === pending);

    const handleConfirm = async () => {
        if (pending && (await run(item, pending))) setPending(null);
    };

    return (
        <>
            <GenericActions
                item={item}
                onView={onView}
                onEdit={onEdit}
                onDelete={onDelete}
                extraItems={actions.map(({ action, label }) => (
                    <DropdownMenuItem
                        key={action}
                        onSelect={() => setPending(action)}
                    >
                        {label}
                    </DropdownMenuItem>
                ))}
            />
            <StatusActionDialog
                config={config}
                processing={processing}
                processingLabel={processingLabel}
                onConfirm={() => void handleConfirm()}
                onCancel={() => setPending(null)}
            />
        </>
    );
}
