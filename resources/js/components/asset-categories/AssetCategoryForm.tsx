'use client';

import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import { AssetCategory } from '@/types/asset-category';
import {
    AssetCategoryFormData,
    assetCategoryFormSchema,
} from '@/utils/schemas';
import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect } from 'react';
import { useForm, type UseFormReturn } from 'react-hook-form';
import { z } from 'zod';

interface AssetCategoryFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: AssetCategory | null;
    onSubmit: (data: AssetCategoryFormData) => void;
    isLoading?: boolean;
}

export const AssetCategoryForm = memo<AssetCategoryFormProps>(
    function AssetCategoryForm({
        open,
        onOpenChange,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        type AssetCategoryFormInput = z.input<typeof assetCategoryFormSchema>;

        const form = useForm<AssetCategoryFormInput>({
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
                    useful_life_months_default:
                        entity.useful_life_months_default,
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
            form={
                form as unknown as UseFormReturn<
                    AssetCategoryFormData,
                    unknown,
                    AssetCategoryFormData
                >
            }
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
