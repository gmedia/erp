'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';

import { AssetLocation, AssetLocationFormData } from '@/types/entity';
import { assetLocationFormSchema } from '@/utils/schemas';

interface AssetLocationFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: AssetLocation | null;
    onSubmit: (data: AssetLocationFormData) => void;
    isLoading?: boolean;
}

const renderBasicInfoSection = () => (
    <>
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
    </>
);

const renderBranchSection = (entity?: AssetLocation | null) => (
    <AsyncSelectField
        name="branch_id"
        label="Branch"
        url="/api/branches"
        placeholder="Select a branch"
        initialLabel={entity?.branch?.name}
    />
);

const renderParentSection = (entity?: AssetLocation | null) => (
    <AsyncSelectField
        name="parent_id"
        label="Parent Location (optional)"
        url="/api/asset-locations"
        placeholder="Select a parent location"
        initialLabel={entity?.parent?.name}
    />
);

const getAssetLocationFormDefaults = (entity?: AssetLocation | null): AssetLocationFormData => {
    if (!entity) {
        return {
            code: '',
            name: '',
            branch_id: '',
            parent_id: '',
        };
    }

    return {
        code: entity.code,
        name: entity.name,
        branch_id: typeof entity.branch === 'object' && entity.branch
            ? String(entity.branch.id)
            : String(entity.branch_id || ''),
        parent_id: typeof entity.parent === 'object' && entity.parent
            ? String(entity.parent.id)
            : String(entity.parent_id || ''),
    };
};

export const AssetLocationForm = memo<AssetLocationFormProps>(function AssetLocationForm({
    open,
    onOpenChange,
    entity,
    onSubmit,
    isLoading = false,
}) {
    const defaultValues = useMemo(
        () => getAssetLocationFormDefaults(entity),
        [entity],
    );

    const form = useForm<AssetLocationFormData>({
        resolver: zodResolver(assetLocationFormSchema),
        defaultValues,
    });

    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);

    const handleSubmit = (data: AssetLocationFormData) => {
        const submitData = {
            ...data,
            parent_id: data.parent_id || null,
        };
        onSubmit(submitData as unknown as AssetLocationFormData);
    };

    return (
        <EntityForm<AssetLocationFormData>
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={entity ? 'Edit Asset Location' : 'Add New Asset Location'}
            onSubmit={handleSubmit}
            isLoading={isLoading}
        >
            {renderBasicInfoSection()}
            {renderBranchSection(entity)}
            {renderParentSection(entity)}
        </EntityForm>
    );
});
