'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import { TextareaField } from '@/components/common/TextareaField';

import { AssetModel, AssetModelFormData } from '@/types/entity';
import { assetModelFormSchema } from '@/utils/schemas';

interface AssetModelFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    assetModel?: AssetModel | null;
    onSubmit: (data: AssetModelFormData) => void;
    isLoading?: boolean;
}

const renderBasicInfoSection = () => (
    <>
        <InputField
            name="model_name"
            label="Model Name"
            placeholder="Enter model name"
        />
        <InputField
            name="manufacturer"
            label="Manufacturer"
            placeholder="Enter manufacturer (optional)"
        />
    </>
);

const renderCategorySection = () => (
    <AsyncSelectField
        name="asset_category_id"
        label="Category"
        url="/api/asset-categories"
        placeholder="Select a category"
    />
);

const renderSpecsSection = () => (
    <TextareaField
        name="specs"
        label="Specifications (JSON)"
        placeholder='{"cpu": "i7", "ram_gb": 16}'
        rows={3}
    />
);

const getAssetModelFormDefaults = (assetModel?: AssetModel | null): AssetModelFormData => {
    if (!assetModel) {
        return {
            model_name: '',
            manufacturer: '',
            asset_category_id: '',
            specs: '',
        };
    }

    return {
        model_name: assetModel.model_name,
        manufacturer: assetModel.manufacturer || '',
        asset_category_id: typeof assetModel.category === 'object'
            ? String(assetModel.category.id)
            : String(assetModel.asset_category_id || ''),
        specs: assetModel.specs ? JSON.stringify(assetModel.specs) : '',
    };
};

export const AssetModelForm = memo<AssetModelFormProps>(function AssetModelForm({
    open,
    onOpenChange,
    assetModel,
    onSubmit,
    isLoading = false,
}) {
    const defaultValues = useMemo(
        () => getAssetModelFormDefaults(assetModel),
        [assetModel],
    );

    const form = useForm<AssetModelFormData>({
        resolver: zodResolver(assetModelFormSchema),
        defaultValues,
    });

    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);

    const handleSubmit = (data: AssetModelFormData) => {
        const submitData = {
            ...data,
            specs: data.specs ? JSON.parse(data.specs) : null,
        };
        onSubmit(submitData as unknown as AssetModelFormData);
    };

    return (
        <EntityForm<AssetModelFormData>
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={assetModel ? 'Edit Asset Model' : 'Add New Asset Model'}
            onSubmit={handleSubmit}
            isLoading={isLoading}
        >
            {renderBasicInfoSection()}
            {renderCategorySection()}
            {renderSpecsSection()}
        </EntityForm>
    );
});
