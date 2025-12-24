'use client';

import * as React from 'react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Form } from '@/components/ui/form';
import NameField from '@/components/common/NameField';
import { FormMessage } from '@/components/ui/form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { simpleEntitySchema } from '@/utils/schemas';
import * as z from 'zod';

interface EntityFormProps<T = Record<string, unknown>> {
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
    form: ReturnType<typeof useForm>;
}

/**
 * EntityForm – a reusable dialog‑form wrapper.
 *
 * It renders a Dialog containing a Form (react‑hook‑form) and places any
 * field JSX passed as children inside the form. Validation is optional via a
 * Zod schema.
 */
export default function EntityForm<T>({
    open,
    onOpenChange,
    title,
    onSubmit,
    children,
    isLoading = false,
    form,
}: EntityFormProps<T>) {
    const handleSubmit = (values: any) => {
        onSubmit(values);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    {/* Optional description can be added as a child of EntityForm if needed */}
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
                                Cancel
                            </Button>
                            <Button type="submit" disabled={isLoading}>
                                {isLoading
                                    ? 'Saving...'
                                    : title.toLowerCase().includes('add')
                                      ? 'Add'
                                      : title.toLowerCase().includes('edit')
                                        ? 'Update'
                                        : 'Submit'}
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
        defaultValues: entity ? { name: entity.name } : undefined,
    });

    return (
        <EntityForm
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={entity ? `Edit ${entityName}` : `Add New ${entityName}`}
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            <NameField name="name" label="Name" placeholder={`e.g., ${entityName}`}>
                <FormMessage />
            </NameField>
        </EntityForm>
    );
}
