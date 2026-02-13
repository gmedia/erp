'use client';

import * as z from 'zod';
import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import CheckboxField from '@/components/common/CheckboxField';
import { TextareaField } from '@/components/common/TextareaField';

import { Product, ProductFormData } from '@/types/entity';
import { productFormSchema } from '@/utils/schemas';

interface ProductFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    product?: Product | null;
    onSubmit: (data: ProductFormData) => void;
    isLoading?: boolean;
}

const renderGeneralInfoSection = () => (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <InputField name="code" label="Code" placeholder="PROD-001" />
        <InputField name="name" label="Name" placeholder="Product Name" />
        <div className="md:col-span-2">
            <TextareaField name="description" label="Description" placeholder="Product description..." />
        </div>
        <AsyncSelectField
            name="category_id"
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
    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 border-t pt-4 mt-2">
        <InputField name="cost" label="Cost" type="number" placeholder="0.00" prefix="Rp" />
        <InputField name="selling_price" label="Selling Price" type="number" placeholder="0.00" prefix="Rp" />
        <InputField name="markup_percentage" label="Markup %" type="number" placeholder="0.00" />
    </div>
);

const renderConfigSection = () => (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 border-t pt-4 mt-2">
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
        <InputField name="trial_period_days" label="Trial Period (Days)" type="number" placeholder="0" />
        <div className="md:col-span-2 grid grid-cols-2 md:grid-cols-3 gap-4 py-2">
            <CheckboxField name="is_recurring" label="Is Recurring" />
            <CheckboxField name="allow_one_time_purchase" label="Allow One-Time Purchase" />
            <CheckboxField name="is_manufactured" label="Is Manufactured" />
            <CheckboxField name="is_purchasable" label="Is Purchasable" />
            <CheckboxField name="is_sellable" label="Is Sellable" />
            <CheckboxField name="is_taxable" label="Is Taxable" />
        </div>
    </div>
);

const renderOtherSection = () => (
    <div className="grid grid-cols-1 gap-4 border-t pt-4 mt-2">
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
        <TextareaField name="notes" label="Notes" placeholder="Additional notes..." />
    </div>
);

const getProductFormDefaults = (product?: Product | null): ProductFormData => {
    if (!product) {
        return {
            code: '',
            name: '',
            description: '',
            type: 'finished_good',
            category_id: '',
            unit_id: '',
            branch_id: '',
            cost: '0',
            selling_price: '0',
            markup_percentage: '',
            billing_model: 'one_time',
            is_recurring: false,
            trial_period_days: '',
            allow_one_time_purchase: true,
            is_manufactured: false,
            is_purchasable: true,
            is_sellable: true,
            is_taxable: true,
            status: 'active',
            notes: '',
        };
    }

    return {
        code: product.code,
        name: product.name,
        description: product.description || '',
        type: product.type,
        category_id: String(product.category.id),
        unit_id: String(product.unit.id),
        branch_id: product.branch ? String(product.branch.id) : '',
        cost: String(product.cost),
        selling_price: String(product.selling_price),
        markup_percentage: product.markup_percentage ? String(product.markup_percentage) : '',
        billing_model: product.billing_model,
        is_recurring: product.is_recurring,
        trial_period_days: product.trial_period_days ? String(product.trial_period_days) : '',
        allow_one_time_purchase: product.allow_one_time_purchase,
        is_manufactured: product.is_manufactured,
        is_purchasable: product.is_purchasable,
        is_sellable: product.is_sellable,
        is_taxable: product.is_taxable,
        status: product.status,
        notes: product.notes || '',
    };
};

export const ProductForm = memo<ProductFormProps>(function ProductForm({
    open,
    onOpenChange,
    product,
    onSubmit,
    isLoading = false,
}) {
    const defaultValues = useMemo(() => getProductFormDefaults(product), [product]);

    const form = useForm<any>({
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
            title={product ? 'Edit Product/Service' : 'Add New Product/Service'}
            onSubmit={onSubmit as any}
            isLoading={isLoading}
        >
            {renderGeneralInfoSection()}
            {renderPricingSection()}
            {renderConfigSection()}
            {renderOtherSection()}
        </EntityForm>
    );
});
