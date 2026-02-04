'use client';

import * as React from 'react';
import { useEffect, memo } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import { assetCategoryFormSchema, AssetCategoryFormData } from '@/utils/schemas';
import { AssetCategory } from '@/types/asset-category';

interface AssetCategoryFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: AssetCategory | null;
    onSubmit: (data: AssetCategoryFormData) => void;
    isLoading?: boolean;
}

export const AssetCategoryForm = memo<AssetCategoryFormProps>(function AssetCategoryForm({
    open,
    onOpenChange,
    entity,
    onSubmit,
    isLoading = false,
}) {
    const form = useForm<AssetCategoryFormData>({
        resolver: zodResolver(assetCategoryFormSchema),
        defaultValues: {
            code: '',
            name: '',
            useful_life_months_default: 0,
        },
    });

    useEffect(() => {
        if (entity) {
            form.reset({
                code: entity.code,
                name: entity.name,
                useful_life_months_default: entity.useful_life_months_default,
            });
        } else {
            form.reset({
                code: '',
                name: '',
                useful_life_months_default: 0,
            });
        }
    }, [entity, form]);

    return (
        <EntityForm<AssetCategoryFormData>
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={entity ? 'Edit Asset Category' : 'Add New Asset Category'}
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            <InputField
                name="code"
                label="Code"
                placeholder="e.g., KND"
            />
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
});
