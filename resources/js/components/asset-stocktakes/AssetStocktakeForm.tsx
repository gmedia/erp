'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import { useTranslation } from '@/contexts/i18n-context';

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
            planned_at: '',
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
        planned_at: assetStocktake.planned_at ? assetStocktake.planned_at : '',
        performed_at: assetStocktake.performed_at
            ? assetStocktake.performed_at
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
        const { t } = useTranslation();
        const fl = (key: string, fallback: string) => {
            const translated = t(`form_fields.asset_stocktakes.${key}`);
            return translated === `form_fields.asset_stocktakes.${key}`
                ? fallback
                : translated;
        };
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
                        ? fl('edit_title', 'Edit Asset Stocktake')
                        : fl('add_title', 'Add New Asset Stocktake')
                }
                onSubmit={onSubmit}
                isLoading={isLoading}
                className="sm:max-w-[600px]"
            >
                <AsyncSelectField
                    key={`branch-select-${activeAssetStocktake?.id || 'new'}`}
                    name="branch_id"
                    label={fl('branch', 'Branch')}
                    url="/api/branches"
                    placeholder={fl('branch_placeholder', 'Select a branch')}
                    initialLabel={activeAssetStocktake?.branch?.name}
                />
                <InputField
                    name="reference"
                    label={fl('reference', 'Reference')}
                    placeholder={fl('reference_placeholder', 'ST-2024-001')}
                />
                <DatePickerField
                    name="planned_at"
                    label={fl('planned_at', 'Planned Date')}
                />
                <DatePickerField
                    name="performed_at"
                    label={fl('performed_at', 'Performed Date')}
                />
                <SelectField
                    name="status"
                    label={fl('status', 'Status')}
                    options={[
                        {
                            value: 'draft',
                            label: fl('status_draft', 'Draft'),
                        },
                        {
                            value: 'in_progress',
                            label: fl('status_in_progress', 'In Progress'),
                        },
                        {
                            value: 'completed',
                            label: fl('status_completed', 'Completed'),
                        },
                        {
                            value: 'cancelled',
                            label: fl('status_cancelled', 'Cancelled'),
                        },
                    ]}
                />
            </EntityForm>
        );
    },
);
