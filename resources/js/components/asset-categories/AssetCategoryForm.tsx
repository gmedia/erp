'use client';

import { memo } from 'react';

import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import { useTranslation } from '@/contexts/i18n-context';
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
        const { t } = useTranslation();
        const fl = (key: string, fallback: string) => {
            const translated = t(`form_fields.asset_categories.${key}`);
            return translated === `form_fields.asset_categories.${key}`
                ? fallback
                : translated;
        };
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
                    entity
                        ? fl('edit_title', 'Edit Asset Category')
                        : fl('add_title', 'Add New Asset Category')
                }
                onSubmit={onSubmit}
                isLoading={isLoading}
            >
                <InputField
                    name="code"
                    label={fl('code', 'Code')}
                    placeholder={fl('code_placeholder', 'e.g., KND')}
                />
                <InputField
                    name="name"
                    label={fl('name', 'Name')}
                    placeholder={fl('name_placeholder', 'e.g., Kendaraan')}
                />
                <InputField
                    name="useful_life_months_default"
                    label={fl(
                        'useful_life_months_default',
                        'Default Useful Life (Months)',
                    )}
                    type="number"
                    placeholder={fl(
                        'useful_life_months_default_placeholder',
                        'e.g., 48',
                    )}
                />
            </EntityForm>
        );
    },
);
