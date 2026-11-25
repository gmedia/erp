'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import { TextareaField } from '@/components/common/TextareaField';
import { useTranslation } from '@/contexts/i18n-context';

import { AssetModel, AssetModelFormData } from '@/types/entity';
import { assetModelFormSchema } from '@/utils/schemas';

interface AssetModelFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: AssetModel | null;
    onSubmit: (data: AssetModelFormData) => void;
    isLoading?: boolean;
}

type FieldLabeler = (key: string, fallback: string) => string;

const renderBasicInfoSection = (fl: FieldLabeler) => (
    <>
        <InputField
            name="model_name"
            label={fl('model_name', 'Model Name')}
            placeholder={fl('model_name_placeholder', 'Enter model name')}
        />
        <InputField
            name="manufacturer"
            label={fl('manufacturer', 'Manufacturer')}
            placeholder={fl(
                'manufacturer_placeholder',
                'Enter manufacturer (optional)',
            )}
        />
    </>
);

const renderCategorySection = (fl: FieldLabeler) => (
    <AsyncSelectField
        name="asset_category_id"
        label={fl('category', 'Category')}
        url="/api/asset-categories"
        placeholder={fl('category_placeholder', 'Select a category')}
    />
);

const renderSpecsSection = (fl: FieldLabeler) => (
    <TextareaField
        name="specs"
        label={fl('specs', 'Specifications (JSON)')}
        placeholder={fl('specs_placeholder', '{"cpu": "i7", "ram_gb": 16}')}
        rows={3}
    />
);

const getAssetModelFormDefaults = (
    entity?: AssetModel | null,
): AssetModelFormData => {
    if (!entity) {
        return {
            model_name: '',
            manufacturer: '',
            asset_category_id: '',
            specs: '',
        };
    }

    return {
        model_name: entity.model_name,
        manufacturer: entity.manufacturer || '',
        asset_category_id:
            typeof entity.category === 'object'
                ? String(entity.category.id)
                : String(entity.asset_category_id || ''),
        specs: entity.specs ? JSON.stringify(entity.specs) : '',
    };
};

export const AssetModelForm = memo<AssetModelFormProps>(
    function AssetModelForm({
        open,
        onOpenChange,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const { t } = useTranslation();
        const fl: FieldLabeler = (key, fallback) => {
            const translated = t(`form_fields.asset_models.${key}`);
            return translated === `form_fields.asset_models.${key}`
                ? fallback
                : translated;
        };
        const defaultValues = useMemo(
            () => getAssetModelFormDefaults(entity),
            [entity],
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
            onSubmit(submitData);
        };

        return (
            <EntityForm<AssetModelFormData>
                form={form}
                open={open}
                onOpenChange={onOpenChange}
                title={
                    entity
                        ? fl('edit_title', 'Edit Asset Model')
                        : fl('add_title', 'Add New Asset Model')
                }
                onSubmit={handleSubmit}
                isLoading={isLoading}
            >
                {renderBasicInfoSection(fl)}
                {renderCategorySection(fl)}
                {renderSpecsSection(fl)}
            </EntityForm>
        );
    },
);
