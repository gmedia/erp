'use client';

import * as React from 'react';
import { useEffect } from 'react';

import NameField from '@/components/common/NameField';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Form, FormMessage } from '@/components/ui/form';
import { useTranslation } from '@/contexts/i18n-context';
import { simpleEntitySchema } from '@/utils/schemas';
import { zodResolver } from '@hookform/resolvers/zod';
import { FieldValues, useForm, UseFormReturn } from 'react-hook-form';
import * as z from 'zod';

interface EntityFormProps<T extends FieldValues = FieldValues> {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    onSubmit: (values: T) => Promise<void> | void;
    /** Optional – kept for backward compatibility; not used inside EntityForm */
    defaultValues?: Partial<T>;
    /** Optional – kept for backward compatibility; not used inside EntityForm */
    schema?: unknown;
    children: React.ReactNode;
    isLoading?: boolean;
    /** The form object returned by react‑hook‑form's useForm */
    form: UseFormReturn<T>;
}

/**
 * EntityForm – a reusable dialog‑form wrapper with improved type safety.
 *
 * It renders a Dialog containing a Form (react‑hook‑form) and places any
 * field JSX passed as children inside the form. Validation is optional via a
 * Zod schema.
 */
export default function EntityForm<T extends FieldValues = FieldValues>({
    open,
    onOpenChange,
    title,
    onSubmit,
    children,
    isLoading = false,
    form,
}: EntityFormProps<T>) {
    const { t } = useTranslation();

    const handleSubmit = React.useCallback(
        (values: T) => {
            onSubmit(values);
        },
        [onSubmit],
    );

    // Determine submit button text based on title
    const submitButtonText = React.useMemo(() => {
        if (isLoading) return t('common.saving');

        const lowerTitle = title.toLowerCase();
        if (lowerTitle.includes('add') || lowerTitle.includes('tambah'))
            return t('common.add');
        if (lowerTitle.includes('edit') || lowerTitle.includes('ubah'))
            return t('common.update');
        if (lowerTitle.includes('create') || lowerTitle.includes('buat'))
            return t('common.create');
        return t('common.submit');
    }, [title, isLoading, t]);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                </DialogHeader>
                <Form {...form}>
                    <form
                        onSubmit={form.handleSubmit(handleSubmit)}
                        className="space-y-4"
                    >
                        {children}
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => onOpenChange(false)}
                                disabled={isLoading}
                            >
                                {t('common.cancel')}
                            </Button>
                            <Button type="submit" disabled={isLoading}>
                                {submitButtonText}
                            </Button>
                        </DialogFooter>
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
}: SimpleEntityFormProps) {
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
