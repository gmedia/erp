'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';
import * as z from 'zod';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import { useTranslation } from '@/contexts/i18n-context';

import { Product, ProductFormData } from '@/types/entity';
import { productFormSchema } from '@/utils/schemas';

interface ProductFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: Product | null;
    onSubmit: (data: ProductFormData) => void;
    isLoading?: boolean;
}

type FormLabelFn = (key: string, fallback: string) => string;

const renderGeneralInfoSection = (fl: FormLabelFn) => (
    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
        <InputField
            name="code"
            label={fl('code', 'Code')}
            placeholder={fl('code_placeholder', 'PROD-001')}
        />
        <InputField
            name="name"
            label={fl('name', 'Name')}
            placeholder={fl('name_placeholder', 'Product Name')}
        />
        <div className="md:col-span-2">
            <TextareaField
                name="description"
                label={fl('description', 'Description')}
                placeholder={fl(
                    'description_placeholder',
                    'Product description...',
                )}
            />
        </div>
        <AsyncSelectField
            name="product_category_id"
            label={fl('category', 'Category')}
            url="/api/product-categories"
            placeholder={fl('category_placeholder', 'Select category')}
        />
        <AsyncSelectField
            name="unit_id"
            label={fl('unit', 'Unit')}
            url="/api/units"
            placeholder={fl('unit_placeholder', 'Select unit')}
        />
        <AsyncSelectField
            name="branch_id"
            label={fl('branch', 'Branch')}
            url="/api/branches"
            placeholder={fl('branch_placeholder', 'Select branch (optional)')}
        />
        <SelectField
            name="type"
            label={fl('type', 'Type')}
            options={[
                {
                    value: 'raw_material',
                    label: fl('type_raw_material', 'Raw Material'),
                },
                {
                    value: 'work_in_progress',
                    label: fl('type_work_in_progress', 'WIP'),
                },
                {
                    value: 'finished_good',
                    label: fl('type_finished_good', 'Finished Good'),
                },
                {
                    value: 'purchased_good',
                    label: fl('type_purchased_good', 'Purchased Good'),
                },
                { value: 'service', label: fl('type_service', 'Service') },
            ]}
            placeholder={fl('type_placeholder', 'Select type')}
        />
    </div>
);

const renderPricingSection = (fl: FormLabelFn) => (
    <div className="mt-2 grid grid-cols-1 gap-4 border-t pt-4 md:grid-cols-2">
        <InputField
            name="cost"
            label={fl('cost', 'Cost')}
            type="number"
            placeholder="0.00"
            prefix="Rp"
        />
        <InputField
            name="selling_price"
            label={fl('selling_price', 'Selling Price')}
            type="number"
            placeholder="0.00"
            prefix="Rp"
        />
    </div>
);

const renderConfigSection = (fl: FormLabelFn) => (
    <div className="mt-2 grid grid-cols-1 gap-4 border-t pt-4 md:grid-cols-2">
        <SelectField
            name="billing_model"
            label={fl('billing_model', 'Billing Model')}
            options={[
                {
                    value: 'one_time',
                    label: fl('billing_model_one_time', 'One Time'),
                },
                {
                    value: 'subscription',
                    label: fl('billing_model_subscription', 'Subscription'),
                },
                { value: 'both', label: fl('billing_model_both', 'Both') },
            ]}
            placeholder={fl('billing_model_placeholder', 'Select model')}
        />
        <SelectField
            name="status"
            label={fl('status', 'Status')}
            options={[
                { value: 'active', label: fl('status_active', 'Active') },
                {
                    value: 'inactive',
                    label: fl('status_inactive', 'Inactive'),
                },
                {
                    value: 'discontinued',
                    label: fl('status_discontinued', 'Discontinued'),
                },
            ]}
            placeholder={fl('status_placeholder', 'Select status')}
        />
    </div>
);

const renderOtherSection = (fl: FormLabelFn) => (
    <div className="mt-2 grid grid-cols-1 gap-4 border-t pt-4">
        <TextareaField
            name="notes"
            label={fl('notes', 'Notes')}
            placeholder={fl('notes_placeholder', 'Additional notes...')}
        />
    </div>
);

const getProductFormDefaults = (product?: Product | null): ProductFormData => {
    if (!product) {
        return {
            code: '',
            name: '',
            description: '',
            type: 'finished_good',
            product_category_id: '',
            unit_id: '',
            branch_id: '',
            cost: '0',
            selling_price: '0',
            billing_model: 'one_time',
            status: 'active',
            notes: '',
        };
    }

    return {
        code: product.code,
        name: product.name,
        description: product.description || '',
        type: product.type,
        product_category_id: String(product.category.id),
        unit_id: String(product.unit.id),
        branch_id: product.branch ? String(product.branch.id) : '',
        cost: String(product.cost),
        selling_price: String(product.selling_price),
        billing_model: product.billing_model,
        status: product.status,
        notes: product.notes || '',
    };
};

export const ProductForm = memo<ProductFormProps>(function ProductForm({
    open,
    onOpenChange,
    entity,
    onSubmit,
    isLoading = false,
}) {
    const { t } = useTranslation();
    const fl: FormLabelFn = (key, fallback) => {
        const translated = t(`form_fields.products.${key}`);
        return translated === `form_fields.products.${key}`
            ? fallback
            : translated;
    };
    const defaultValues = useMemo(
        () => getProductFormDefaults(entity),
        [entity],
    );

    const form = useForm<z.input<typeof productFormSchema>>({
        resolver: zodResolver(productFormSchema),
        defaultValues,
    });

    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);

    return (
        <EntityForm
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={
                entity
                    ? fl('edit_title', 'Edit Product/Service')
                    : fl('add_title', 'Add New Product/Service')
            }
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            {renderGeneralInfoSection(fl)}
            {renderPricingSection(fl)}
            {renderConfigSection(fl)}
            {renderOtherSection(fl)}
        </EntityForm>
    );
});
