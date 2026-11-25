'use client';

import { memo } from 'react';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import { useTranslation } from '@/contexts/i18n-context';
import { useEntityForm } from '@/hooks/useEntityForm';

import { AssetLocation, AssetLocationFormData } from '@/types/entity';
import { assetLocationFormSchema } from '@/utils/schemas';

interface AssetLocationFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: AssetLocation | null;
    onSubmit: (data: AssetLocationFormData) => void;
    isLoading?: boolean;
}

const getAssetLocationFormDefaults = (
    entity?: AssetLocation | null,
): AssetLocationFormData => {
    if (!entity) {
        return { code: '', name: '', branch_id: '', parent_id: '' };
    }

    return {
        code: entity.code,
        name: entity.name,
        branch_id:
            typeof entity.branch === 'object' && entity.branch
                ? String(entity.branch.id)
                : String(entity.branch_id || ''),
        parent_id:
            typeof entity.parent === 'object' && entity.parent
                ? String(entity.parent.id)
                : String(entity.parent_id || ''),
    };
};

export const AssetLocationForm = memo<AssetLocationFormProps>(
    function AssetLocationForm({
        open,
        onOpenChange,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const { t } = useTranslation();
        const fl = (key: string, fallback: string) => {
            const translated = t(`form_fields.asset_locations.${key}`);
            return translated === `form_fields.asset_locations.${key}`
                ? fallback
                : translated;
        };
        const form = useEntityForm<AssetLocationFormData, AssetLocation>({
            schema: assetLocationFormSchema,
            getDefaults: getAssetLocationFormDefaults,
            entity,
        });

        const handleSubmit = (data: AssetLocationFormData) => {
            onSubmit({
                ...data,
                parent_id: data.parent_id || null,
            } as unknown as AssetLocationFormData);
        };

        return (
            <EntityForm<AssetLocationFormData>
                form={form}
                open={open}
                onOpenChange={onOpenChange}
                title={
                    entity
                        ? fl('edit_title', 'Edit Asset Location')
                        : fl('add_title', 'Add New Asset Location')
                }
                onSubmit={handleSubmit}
                isLoading={isLoading}
            >
                <InputField
                    name="code"
                    label={fl('code', 'Code')}
                    placeholder={fl('code_placeholder', 'Enter location code')}
                />
                <InputField
                    name="name"
                    label={fl('name', 'Name')}
                    placeholder={fl('name_placeholder', 'Enter location name')}
                />
                <AsyncSelectField
                    name="branch_id"
                    label={fl('branch', 'Branch')}
                    url="/api/branches"
                    placeholder={fl('branch_placeholder', 'Select a branch')}
                    initialLabel={entity?.branch?.name}
                />
                <AsyncSelectField
                    name="parent_id"
                    label={fl('parent', 'Parent Location (optional)')}
                    url="/api/asset-locations"
                    placeholder={fl(
                        'parent_placeholder',
                        'Select a parent location',
                    )}
                    initialLabel={entity?.parent?.name}
                />
            </EntityForm>
        );
    },
);
