'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';

import { type Warehouse, type WarehouseFormData } from '@/types/entity';
import { warehouseFormSchema } from '@/utils/schemas';

interface WarehouseFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: Warehouse | null;
    onSubmit: (data: WarehouseFormData) => void;
    isLoading?: boolean;
}

const renderBasicInfoSection = () => (
    <>
        <InputField
            name="code"
            label="Code"
            placeholder="Enter warehouse code"
        />
        <InputField
            name="name"
            label="Name"
            placeholder="Enter warehouse name"
        />
    </>
);

const renderBranchSection = (entity?: Warehouse | null) => (
    <AsyncSelectField
        name="branch_id"
        label="Branch"
        url="/api/branches"
        placeholder="Select a branch"
        initialLabel={entity?.branch?.name}
    />
);

const getWarehouseFormDefaults = (
    entity?: Warehouse | null,
): WarehouseFormData => {
    if (!entity) {
        return {
            code: '',
            name: '',
            branch_id: '',
        };
    }

    return {
        code: entity.code,
        name: entity.name,
        branch_id:
            typeof entity.branch === 'object' && entity.branch
                ? String(entity.branch.id)
                : String(entity.branch_id || ''),
    };
};

export const WarehouseForm = memo<WarehouseFormProps>(function WarehouseForm({
    open,
    onOpenChange,
    entity,
    onSubmit,
    isLoading = false,
}) {
    const defaultValues = useMemo(
        () => getWarehouseFormDefaults(entity),
        [entity],
    );

    const form = useForm<WarehouseFormData>({
        resolver: zodResolver(warehouseFormSchema),
        defaultValues,
    });

    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);

    return (
        <EntityForm<WarehouseFormData>
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={entity ? 'Edit Warehouse' : 'Add New Warehouse'}
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            {renderBasicInfoSection()}
            {renderBranchSection(entity)}
        </EntityForm>
    );
});
