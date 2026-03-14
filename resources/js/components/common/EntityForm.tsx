'use client';

import * as React from 'react';
import { useEffect } from 'react';

import NameField from '@/components/common/NameField';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Form, FormMessage } from '@/components/ui/form';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useTranslation } from '@/contexts/i18n-context';
import { cn } from '@/lib/utils';
import { simpleEntitySchema } from '@/utils/schemas';
import { zodResolver } from '@hookform/resolvers/zod';
import { FieldValues, useForm, UseFormReturn } from 'react-hook-form';
import * as z from 'zod';

interface EntityFormProps<
    TFieldValues extends FieldValues = FieldValues,
    TTransformedValues extends FieldValues = TFieldValues,
> {
    readonly open: boolean;
    readonly onOpenChange: (open: boolean) => void;
    readonly title: string;
    readonly onSubmit: (values: TTransformedValues) => Promise<void> | void;
    /** Optional – kept for backward compatibility; not used inside EntityForm */
    readonly defaultValues?: Partial<TFieldValues>;
    /** Optional – kept for backward compatibility; not used inside EntityForm */
    readonly schema?: unknown;
    readonly children: React.ReactNode;
    readonly isLoading?: boolean;
    /** The form object returned by react‑hook‑form's useForm */
    readonly form: UseFormReturn<TFieldValues, unknown, TTransformedValues>;
    /** Optional – disable the submit button manually (e.g. for custom validation) */
    readonly submitDisabled?: boolean;
    /** Optional – custom class name for the DialogContent (e.g. for wider forms) */
    readonly className?: string;
    /** Optional – custom label for the submit button */
    readonly submitLabel?: string;
}

/**
 * EntityForm – a reusable dialog‑form wrapper with improved type safety.
 *
 * It renders a Dialog containing a Form (react‑hook‑form) and places any
 * field JSX passed as children inside the form. Validation is optional via a
 * Zod schema.
 */
export default function EntityForm<
    TFieldValues extends FieldValues = FieldValues,
    TTransformedValues extends FieldValues = TFieldValues,
>({
    open,
    onOpenChange,
    title,
    onSubmit,
    children,
    isLoading = false,
    form,
    submitDisabled = false,
    className,
    submitLabel,
}: EntityFormProps<TFieldValues, TTransformedValues>) {
    const { t } = useTranslation();

    const handleSubmit = React.useCallback(
        (values: TTransformedValues) => {
            onSubmit(values);
        },
        [onSubmit],
    );

    // Determine submit button text based on title
    const submitButtonText = React.useMemo(() => {
        if (isLoading) return t('common.saving');
        if (submitLabel) return submitLabel;

        const lowerTitle = title.toLowerCase();
        if (lowerTitle.includes('add') || lowerTitle.includes('tambah'))
            return t('common.add');
        if (lowerTitle.includes('edit') || lowerTitle.includes('ubah'))
            return t('common.update');
        if (lowerTitle.includes('create') || lowerTitle.includes('buat'))
            return t('common.create');
        return t('common.submit');
    }, [title, isLoading, t, submitLabel]);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                className={cn(
                    'flex max-h-[90vh] flex-col overflow-hidden p-0 sm:max-w-[600px]',
                    className,
                )}
            >
                <div className="shrink-0 p-6 pb-2">
                    <DialogHeader>
                        <DialogTitle>{title}</DialogTitle>
                        <DialogDescription>
                            {t('common.fill_details')}
                        </DialogDescription>
                    </DialogHeader>
                </div>
                <Form {...form}>
                    <form
                        onSubmit={form.handleSubmit(handleSubmit)}
                        className="flex min-h-0 flex-1 flex-col"
                    >
                        <ScrollArea className="flex-1 px-6">
                            <div className="space-y-4 py-1 pr-6">
                                {children}
                            </div>
                        </ScrollArea>
                        <div className="shrink-0 p-6 pt-2">
                            <DialogFooter className="border-t pt-4">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => onOpenChange(false)}
                                    disabled={isLoading}
                                >
                                    {t('common.cancel')}
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={isLoading || submitDisabled}
                                >
                                    {submitButtonText}
                                </Button>
                            </DialogFooter>
                        </div>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    );
}

// Re-export for backward compatibility
export type SimpleEntityFormData = z.infer<typeof simpleEntitySchema>;

interface SimpleEntityFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: { name: string } | null;
    onSubmit: (data: SimpleEntityFormData) => void;
    isLoading?: boolean;
    entityName: string;
}

/**
 * SimpleEntityForm – a reusable form for simple entities with just a name field.
 * Used for departments, positions, and other basic entities.
 */
export function SimpleEntityForm({
    open,
    onOpenChange,
    entity,
    onSubmit,
    isLoading = false,
    entityName,
}: Readonly<SimpleEntityFormProps>) {
    const form = useForm<SimpleEntityFormData>({
        resolver: zodResolver(simpleEntitySchema),
        defaultValues: entity ? { name: entity.name } : { name: '' },
    });

    // Reset form when entity changes (for edit mode)
    useEffect(() => {
        form.reset(entity ? { name: entity.name } : { name: '' });
    }, [form, entity]);

    return (
        <EntityForm
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={entity ? `Edit ${entityName}` : `Add New ${entityName}`}
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            <NameField
                name="name"
                label="Name"
                placeholder={`e.g., ${entityName}`}
            >
                <FormMessage />
            </NameField>
        </EntityForm>
    );
}
