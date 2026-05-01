'use client';

import { memo } from 'react';

import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import { useEntityForm } from '@/hooks/useEntityForm';
import { AssetCategory } from '@/types/asset-category';
import {
    AssetCategoryFormData,
    assetCategoryFormSchema,
} from '@/utils/schemas';

interface AssetCategoryFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: AssetCategory | null;
    onSubmit: (data: AssetCategoryFormData) => void;
    isLoading?: boolean;
}

const getDefaults = (entity?: AssetCategory | null): AssetCategoryFormData => ({
    code: entity?.code || '',
    name: entity?.name || '',
    useful_life_months_default: entity?.useful_life_months_default || 0,
});

export const AssetCategoryForm = memo<AssetCategoryFormProps>(
    function AssetCategoryForm({
        open,
        onOpenChange,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const form = useEntityForm<AssetCategoryFormData, AssetCategory>({
            schema: assetCategoryFormSchema,
            getDefaults,
            entity,
        });

        return (
            <EntityForm<AssetCategoryFormData>
                form={form}
                open={open}
                onOpenChange={onOpenChange}
                title={
                    entity ? 'Edit Asset Category' : 'Add New Asset Category'
                }
                onSubmit={onSubmit}
                isLoading={isLoading}
            >
                <InputField name="code" label="Code" placeholder="e.g., KND" />
                <InputField
                    name="name"
                    label="Name"
                    placeholder="e.g., Kendaraan"
                />
                <InputField
                    name="useful_life_months_default"
                    label="Default Useful Life (Months)"
                    type="number"
                    placeholder="e.g., 48"
                />
            </EntityForm>
        );
    },
);
