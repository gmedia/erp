'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useForm, type UseFormReturn } from 'react-hook-form';
import * as z from 'zod';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';

import { Product, ProductFormData } from '@/types/entity';
import { productFormSchema } from '@/utils/schemas';

interface ProductFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: Product | null;
    onSubmit: (data: ProductFormData) => void;
    isLoading?: boolean;
}

const renderGeneralInfoSection = () => (
    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
        <InputField name="code" label="Code" placeholder="PROD-001" />
        <InputField name="name" label="Name" placeholder="Product Name" />
        <div className="md:col-span-2">
            <TextareaField
                name="description"
                label="Description"
                placeholder="Product description..."
            />
        </div>
        <AsyncSelectField
            name="product_category_id"
            label="Category"
            url="/api/product-categories"
            placeholder="Select category"
        />
        <AsyncSelectField
            name="unit_id"
            label="Unit"
            url="/api/units"
            placeholder="Select unit"
        />
        <AsyncSelectField
            name="branch_id"
            label="Branch"
            url="/api/branches"
            placeholder="Select branch (optional)"
        />
        <SelectField
            name="type"
            label="Type"
            options={[
                { value: 'raw_material', label: 'Raw Material' },
                { value: 'work_in_progress', label: 'WIP' },
                { value: 'finished_good', label: 'Finished Good' },
                { value: 'purchased_good', label: 'Purchased Good' },
                { value: 'service', label: 'Service' },
            ]}
            placeholder="Select type"
        />
    </div>
);

const renderPricingSection = () => (
    <div className="mt-2 grid grid-cols-1 gap-4 border-t pt-4 md:grid-cols-2">
        <InputField
            name="cost"
            label="Cost"
            type="number"
            placeholder="0.00"
            prefix="Rp"
        />
        <InputField
            name="selling_price"
            label="Selling Price"
            type="number"
            placeholder="0.00"
            prefix="Rp"
        />
    </div>
);

const renderConfigSection = () => (
    <div className="mt-2 grid grid-cols-1 gap-4 border-t pt-4 md:grid-cols-2">
        <SelectField
            name="billing_model"
            label="Billing Model"
            options={[
                { value: 'one_time', label: 'One Time' },
                { value: 'subscription', label: 'Subscription' },
                { value: 'both', label: 'Both' },
            ]}
            placeholder="Select model"
        />
        <SelectField
            name="status"
            label="Status"
            options={[
                { value: 'active', label: 'Active' },
                { value: 'inactive', label: 'Inactive' },
                { value: 'discontinued', label: 'Discontinued' },
            ]}
            placeholder="Select status"
        />
    </div>
);

const renderOtherSection = () => (
    <div className="mt-2 grid grid-cols-1 gap-4 border-t pt-4">
        <TextareaField
            name="notes"
            label="Notes"
            placeholder="Additional notes..."
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
            form={
                form as unknown as UseFormReturn<
                    ProductFormData,
                    unknown,
                    ProductFormData
                >
            }
            open={open}
            onOpenChange={onOpenChange}
            title={entity ? 'Edit Product/Service' : 'Add New Product/Service'}
            onSubmit={
                onSubmit as unknown as (
                    data: z.input<typeof productFormSchema>,
                ) => void
            }
            isLoading={isLoading}
        >
            {renderGeneralInfoSection()}
            {renderPricingSection()}
            {renderConfigSection()}
            {renderOtherSection()}
        </EntityForm>
    );
});
