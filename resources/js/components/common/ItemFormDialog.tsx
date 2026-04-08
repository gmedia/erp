'use client';

import { type ReactNode } from 'react';
import {
    type FieldValues,
    type Path,
    type PathValue,
    type UseFormReturn,
} from 'react-hook-form';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Form } from '@/components/ui/form';

interface ItemFormDialogShellProps<TFormValues extends FieldValues> {
    readonly open: boolean;
    readonly onOpenChange: (open: boolean) => void;
    readonly item: TFormValues | null;
    readonly form: UseFormReturn<TFormValues>;
    readonly onSave: (data: TFormValues) => void;
    readonly itemDescription: string;
    readonly children: ReactNode;
}

interface ItemEntitySelectFieldProps<TFormValues extends FieldValues> {
    readonly form: UseFormReturn<TFormValues>;
    readonly open: boolean;
    readonly name: Path<TFormValues>;
    readonly labelName: Path<TFormValues>;
    readonly label: string;
    readonly url: string;
    readonly placeholder: string;
    readonly initialId?: string;
    readonly initialLabel?: string;
}

function buildAsyncFieldKey(
    fieldName: string,
    initialId: string | undefined,
    open: boolean,
) {
    return `${fieldName}-${initialId || 'new'}-${open ? 'open' : 'closed'}`;
}

export function ItemFormDialogShell<TFormValues extends FieldValues>({
    open,
    onOpenChange,
    item,
    form,
    onSave,
    itemDescription,
    children,
}: Readonly<ItemFormDialogShellProps<TFormValues>>) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{item ? 'Edit Item' : 'Add Item'}</DialogTitle>
                    <DialogDescription className="sr-only">
                        {item
                            ? `Edit ${itemDescription}.`
                            : `Add ${itemDescription}.`}
                    </DialogDescription>
                </DialogHeader>

                <Form {...form}>
                    <form
                        onSubmit={(event) => {
                            event.stopPropagation();
                            form.handleSubmit(onSave)(event);
                        }}
                        className="space-y-4"
                    >
                        {children}

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => onOpenChange(false)}
                            >
                                Cancel
                            </Button>
                            <Button type="submit">
                                {item ? 'Update Item' : 'Save Item'}
                            </Button>
                        </DialogFooter>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export function ItemProductSelectField<TFormValues extends FieldValues>({
    form,
    open,
    name,
    labelName,
    label,
    url,
    placeholder,
    initialId,
    initialLabel,
}: Readonly<ItemEntitySelectFieldProps<TFormValues>>) {
    return (
        <AsyncSelectField<{ name?: string }>
            key={buildAsyncFieldKey(String(name), initialId, open)}
            name={String(name)}
            label={label}
            url={url}
            placeholder={placeholder}
            initialLabel={initialLabel}
            onItemSelect={(item) => {
                form.setValue(
                    labelName,
                    (item?.name || '') as PathValue<
                        TFormValues,
                        typeof labelName
                    >,
                    {
                        shouldDirty: true,
                    },
                );
            }}
        />
    );
}

export function ItemUnitSelectField<TFormValues extends FieldValues>(
    props: Readonly<ItemEntitySelectFieldProps<TFormValues>>,
) {
    return <ItemProductSelectField {...props} />;
}
