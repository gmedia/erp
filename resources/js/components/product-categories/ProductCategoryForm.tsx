'use client';

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
import { useEntityForm } from '@/hooks/useEntityForm';
import {
    productCategoryFormSchema,
    type ProductCategoryFormData,
} from '@/utils/schemas';

interface ProductCategoryFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: { name: string; description?: string | null } | null;
    onSubmit: (data: ProductCategoryFormData) => void;
    isLoading?: boolean;
}

type ProductCategoryEntity = { name: string; description?: string | null };

const getDefaults = (
    entity?: ProductCategoryEntity | null,
): ProductCategoryFormData => ({
    name: entity?.name || '',
    description: entity?.description || '',
});

/**
 * ProductCategoryForm – a custom form for product categories with name and description.
 */
export function ProductCategoryForm({
    open,
    onOpenChange,
    entity,
    onSubmit,
    isLoading = false,
}: Readonly<ProductCategoryFormProps>) {
    const form = useEntityForm<ProductCategoryFormData, ProductCategoryEntity>({
        schema: productCategoryFormSchema,
        getDefaults,
        entity,
    });

    return (
        <EntityForm
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={
                entity ? 'Edit Product Category' : 'Add New Product Category'
            }
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            <NameField name="name" label="Name" placeholder="e.g., Electronics">
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
