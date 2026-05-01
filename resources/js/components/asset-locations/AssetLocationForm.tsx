'use client';

import { memo } from 'react';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
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
                    entity ? 'Edit Asset Location' : 'Add New Asset Location'
                }
                onSubmit={handleSubmit}
                isLoading={isLoading}
            >
                <InputField
                    name="code"
                    label="Code"
                    placeholder="Enter location code"
                />
                <InputField
                    name="name"
                    label="Name"
                    placeholder="Enter location name"
                />
                <AsyncSelectField
                    name="branch_id"
                    label="Branch"
                    url="/api/branches"
                    placeholder="Select a branch"
                    initialLabel={entity?.branch?.name}
                />
                <AsyncSelectField
                    name="parent_id"
                    label="Parent Location (optional)"
                    url="/api/asset-locations"
                    placeholder="Select a parent location"
                    initialLabel={entity?.parent?.name}
                />
            </EntityForm>
        );
    },
);
