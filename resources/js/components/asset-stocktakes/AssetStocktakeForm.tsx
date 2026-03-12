'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';

import { AssetStocktake } from '@/types/asset-stocktake';
import {
    AssetStocktakeFormData,
    assetStocktakeFormSchema,
} from '@/utils/schemas';

interface AssetStocktakeFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    assetStocktake?: AssetStocktake | null;
    item?: AssetStocktake | null;
    entity?: AssetStocktake | null;
    onSubmit: (data: AssetStocktakeFormData) => void;
    isLoading?: boolean;
}

const toOptionalSelectValue = (
    primaryValue: string | number | null | undefined,
    fallbackValue?: string | number | null,
) => {
    if (
        primaryValue !== null &&
        primaryValue !== undefined &&
        primaryValue !== ''
    ) {
        return String(primaryValue);
    }

    if (
        fallbackValue !== null &&
        fallbackValue !== undefined &&
        fallbackValue !== ''
    ) {
        return String(fallbackValue);
    }

    return '';
};

const getAssetStocktakeFormDefaults = (
    assetStocktake?: AssetStocktake | null,
): AssetStocktakeFormData => {
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
        branch_id: toOptionalSelectValue(
            assetStocktake.branch_id,
            assetStocktake.branch?.id,
        ),
        reference: assetStocktake.reference || '',
        planned_at: assetStocktake.planned_at
            ? new Date(assetStocktake.planned_at)
            : new Date(),
        performed_at: assetStocktake.performed_at
            ? new Date(assetStocktake.performed_at)
            : null,
        status: assetStocktake.status,
    };
};

export const AssetStocktakeForm = memo<AssetStocktakeFormProps>(
    function AssetStocktakeForm({
        open,
        onOpenChange,
        assetStocktake,
        item,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const activeAssetStocktake = assetStocktake || item || entity;

        const defaultValues = useMemo(
            () => getAssetStocktakeFormDefaults(activeAssetStocktake),
            [activeAssetStocktake],
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
                title={
                    activeAssetStocktake
                        ? 'Edit Asset Stocktake'
                        : 'Add New Asset Stocktake'
                }
                onSubmit={onSubmit}
                isLoading={isLoading}
                className="sm:max-w-[600px]"
            >
                <AsyncSelectField
                    key={`branch-select-${activeAssetStocktake?.id || 'new'}`}
                    name="branch_id"
                    label="Branch"
                    url="/api/branches"
                    placeholder="Select a branch"
                    initialLabel={activeAssetStocktake?.branch?.name}
                />
                <InputField
                    name="reference"
                    label="Reference"
                    placeholder="ST-2024-001"
                />
                <DatePickerField name="planned_at" label="Planned Date" />
                <DatePickerField name="performed_at" label="Performed Date" />
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
            </EntityForm>
        );
    },
);
