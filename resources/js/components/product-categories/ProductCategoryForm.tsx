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
import { useTranslation } from '@/contexts/i18n-context';
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
    const { t } = useTranslation();
    const tf = (key: string, fallback: string) => {
        const translated = t(`form_fields.product_categories.${key}`);
        return translated === `form_fields.product_categories.${key}`
            ? fallback
            : translated;
    };
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
                entity
                    ? tf('edit_title', 'Edit Product Category')
                    : tf('add_title', 'Add New Product Category')
            }
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            <NameField
                name="name"
                label={{
                    key: 'form_fields.product_categories.name',
                    fallback: 'Name',
                }}
                placeholder={{
                    key: 'form_fields.product_categories.name_placeholder',
                    fallback: 'e.g., Electronics',
                }}
            >
                <FormMessage />
            </NameField>

            <FormField
                control={form.control}
                name="description"
                render={({ field }) => (
                    <FormItem>
                        <FormLabel>
                            {tf('description', 'Description')}
                        </FormLabel>
                        <FormControl>
                            <Textarea
                                placeholder={tf(
                                    'description_placeholder',
                                    'Describe this category...',
                                )}
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
