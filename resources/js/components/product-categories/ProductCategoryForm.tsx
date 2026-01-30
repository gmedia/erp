'use client';

import * as React from 'react';
import { useEffect } from 'react';

import EntityForm from '@/components/common/EntityForm';
import NameField from '@/components/common/NameField';
import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Textarea } from '@/components/ui/textarea';
import { productCategoryFormSchema, type ProductCategoryFormData } from '@/utils/schemas';
import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';

interface ProductCategoryFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: { name: string; description?: string | null } | null;
    onSubmit: (data: ProductCategoryFormData) => void;
    isLoading?: boolean;
}

/**
 * ProductCategoryForm – a custom form for product categories with name and description.
 */
export function ProductCategoryForm({
    open,
    onOpenChange,
    entity,
    onSubmit,
    isLoading = false,
}: ProductCategoryFormProps) {
    const form = useForm<ProductCategoryFormData>({
        resolver: zodResolver(productCategoryFormSchema),
        defaultValues: {
            name: entity?.name || '',
            description: entity?.description || '',
        },
    });

    // Reset form when entity changes (for edit mode)
    useEffect(() => {
        if (open) {
            form.reset({
                name: entity?.name || '',
                description: entity?.description || '',
            });
        }
    }, [form, entity, open]);

    return (
        <EntityForm
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={entity ? 'Edit Product Category' : 'Add New Product Category'}
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            <NameField
                name="name"
                label="Name"
                placeholder="e.g., Electronics"
            >
                <FormMessage />
            </NameField>

            <FormField
                control={form.control}
                name="description"
                render={({ field }) => (
                    <FormItem>
                        <FormLabel>Description</FormLabel>
                        <FormControl>
                            <Textarea
                                placeholder="Describe this category..."
                                className="min-h-[100px] resize-none"
                                {...field}
                                value={field.value || ''}
                            />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                )}
            />
        </EntityForm>
    );
}
