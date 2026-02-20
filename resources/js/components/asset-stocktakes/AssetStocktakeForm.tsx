'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import { SelectField } from '@/components/common/SelectField';
import { DatePickerField } from '@/components/common/DatePickerField';

import { AssetStocktake } from '@/types/asset-stocktake';
import { assetStocktakeFormSchema, AssetStocktakeFormData } from '@/utils/schemas';

interface AssetStocktakeFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    assetStocktake?: AssetStocktake | null;
    onSubmit: (data: AssetStocktakeFormData) => void;
    isLoading?: boolean;
}

const renderFields = () => (
    <>
        <AsyncSelectField
            name="branch_id"
            label="Branch"
            url="/api/branches"
            placeholder="Select a branch"
        />
        <InputField
            name="reference"
            label="Reference"
            placeholder="ST-2024-001"
        />
        <DatePickerField
            name="planned_at"
            label="Planned Date"
        />
        <DatePickerField
            name="performed_at"
            label="Performed Date"
        />
        <SelectField
            name="status"
            label="Status"
            options={[
                { value: 'draft', label: 'Draft' },
                { value: 'in_progress', label: 'In Progress' },
                { value: 'completed', label: 'Completed' },
                { value: 'cancelled', label: 'Cancelled' },
            ]}
        />
    </>
);

const getAssetStocktakeFormDefaults = (assetStocktake?: AssetStocktake | null): AssetStocktakeFormData => {
    if (!assetStocktake) {
        return {
            branch_id: '',
            reference: '',
            planned_at: new Date(),
            performed_at: null,
            status: 'draft',
        };
    }

    return {
        branch_id: String(assetStocktake.branch_id),
        reference: assetStocktake.reference,
        planned_at: new Date(assetStocktake.planned_at),
        performed_at: assetStocktake.performed_at ? new Date(assetStocktake.performed_at) : null,
        status: assetStocktake.status,
    };
};

export const AssetStocktakeForm = memo<AssetStocktakeFormProps>(function AssetStocktakeForm({
    open,
    onOpenChange,
    assetStocktake,
    onSubmit,
    isLoading = false,
}) {
    const defaultValues = useMemo(
        () => getAssetStocktakeFormDefaults(assetStocktake),
        [assetStocktake],
    );

    const form = useForm<AssetStocktakeFormData>({
        resolver: zodResolver(assetStocktakeFormSchema),
        defaultValues,
    });

    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);

    return (
        <EntityForm<AssetStocktakeFormData>
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={assetStocktake ? 'Edit Asset Stocktake' : 'Add New Asset Stocktake'}
            onSubmit={onSubmit}
            isLoading={isLoading}
            className="sm:max-w-[600px]"
        >
            {renderFields()}
        </EntityForm>
    );
});
