'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { format } from 'date-fns';
import { memo, useEffect, useMemo } from 'react';
import { useForm, type UseFormReturn } from 'react-hook-form';
import * as z from 'zod';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import { Asset, AssetMovement } from '@/types/asset';

import {
    AssetMovementFormData,
    assetMovementFormSchema,
} from '@/utils/schemas';

interface AssetMovementFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    item?: AssetMovement | null; // For editing
    asset?: Asset | null; // For creating from profile
    onSubmit: (data: AssetMovementFormData) => void;
    isLoading?: boolean;
}

const toOptionalSelectValue = (value: string | number | null | undefined) => {
    if (typeof value === 'string' || typeof value === 'number') {
        return String(value);
    }

    return '';
};

const ASSET_MOVEMENT_TYPES = [
    'transfer',
    'assign',
    'return',
    'dispose',
    'adjustment',
] as const;

const isAssetMovementType = (
    value: string | null | undefined,
): value is AssetMovementFormData['movement_type'] =>
    !!value &&
    ASSET_MOVEMENT_TYPES.includes(
        value as (typeof ASSET_MOVEMENT_TYPES)[number],
    );

const getAssetMovementFormDefaults = (
    item?: AssetMovement | null,
    asset?: Asset | null,
): AssetMovementFormData => {
    if (item) {
        return {
            asset_id: toOptionalSelectValue(item.asset_id),
            movement_type: isAssetMovementType(item.movement_type)
                ? item.movement_type
                : 'transfer',
            moved_at: item.moved_at ? new Date(item.moved_at) : new Date(),
            to_branch_id: toOptionalSelectValue(item.to_branch_id),
            to_location_id: toOptionalSelectValue(item.to_location_id),
            to_department_id: toOptionalSelectValue(item.to_department_id),
            to_employee_id: toOptionalSelectValue(item.to_employee_id),
            reference: item.reference || '',
            notes: item.notes || '',
        };
    }

    return {
        asset_id: toOptionalSelectValue(asset?.id),
        movement_type: 'transfer',
        moved_at: new Date(),
        to_branch_id: toOptionalSelectValue(asset?.branch_id),
        to_location_id: toOptionalSelectValue(asset?.asset_location_id),
        to_department_id: toOptionalSelectValue(asset?.department_id),
        to_employee_id: toOptionalSelectValue(asset?.employee_id),
        reference: '',
        notes: '',
    };
};

export const AssetMovementForm = memo<AssetMovementFormProps>(
    function AssetMovementForm({
        open,
        onOpenChange,
        item,
        asset,
        onSubmit,
        isLoading = false,
    }) {
        const defaultValues = useMemo(
            () => getAssetMovementFormDefaults(item, asset),
            [item, asset],
        );

        const form = useForm<z.input<typeof assetMovementFormSchema>>({
            resolver: zodResolver(assetMovementFormSchema),
            defaultValues,
        });

        const movementType = form.watch('movement_type');
        const toBranchId = form.watch('to_branch_id');

        useEffect(() => {
            if (open) {
                form.reset(defaultValues);
            }
        }, [open, form, defaultValues]);

        const handleFormSubmit = (data: AssetMovementFormData) => {
            onSubmit({
                ...data,
                moved_at: format(
                    data.moved_at,
                    'yyyy-MM-dd HH:mm:ss',
                ) as unknown as Date,
            });
        };

        const isEdit = !!item;
        let formTitle = 'Record Asset Movement';

        if (isEdit) {
            formTitle = 'Edit Movement';
        } else if (asset) {
            formTitle = `Record Movement for ${asset.asset_code}`;
        }

        return (
            <EntityForm<AssetMovementFormData>
                form={
                    form as unknown as UseFormReturn<
                        AssetMovementFormData,
                        unknown,
                        AssetMovementFormData
                    >
                }
                open={open}
                onOpenChange={onOpenChange}
                title={formTitle}
                submitLabel={isEdit ? 'Update Movement' : 'Record Movement'}
                onSubmit={handleFormSubmit}
                isLoading={isLoading}
                className="sm:max-w-[600px]"
            >
                {open && (
                    <div className="space-y-6">
                        {!asset && !isEdit && (
                            <div className="grid grid-cols-1 gap-4">
                                <AsyncSelectField
                                    name="asset_id"
                                    label="Asset"
                                    url="/api/assets"
                                    placeholder="Select asset"
                                />
                            </div>
                        )}

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <SelectField
                                name="movement_type"
                                label="Movement Type"
                                disabled={isEdit}
                                options={[
                                    {
                                        value: 'transfer',
                                        label: 'Transfer (Location Change)',
                                    },
                                    {
                                        value: 'assign',
                                        label: 'Assign (PIC Change)',
                                    },
                                    { value: 'return', label: 'Return' },
                                    { value: 'dispose', label: 'Dispose' },
                                    {
                                        value: 'adjustment',
                                        label: 'Adjustment',
                                    },
                                ]}
                            />
                            <DatePickerField
                                name="moved_at"
                                label="Movement Date"
                            />
                        </div>

                        {!isEdit && <hr />}

                        {movementType === 'transfer' && !isEdit && (
                            <div className="space-y-4">
                                <h4 className="text-sm font-medium">
                                    Destination Details
                                </h4>
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <AsyncSelectField
                                        name="to_branch_id"
                                        label="To Branch"
                                        url="/api/branches"
                                        placeholder="Select destination branch"
                                        initialLabel={asset?.branch?.name}
                                    />
                                    <AsyncSelectField
                                        name="to_location_id"
                                        label="To Location"
                                        url={
                                            toBranchId
                                                ? `/api/asset-locations?branch_id=${toBranchId}`
                                                : '/api/asset-locations'
                                        }
                                        placeholder="Select destination location"
                                        key={`location-select-${toBranchId}`}
                                        initialLabel={asset?.location?.name}
                                    />
                                </div>
                            </div>
                        )}

                        {movementType === 'assign' && !isEdit && (
                            <div className="space-y-4">
                                <h4 className="text-sm font-medium">
                                    Assignment Details
                                </h4>
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <AsyncSelectField
                                        name="to_department_id"
                                        label="To Department"
                                        url="/api/departments"
                                        placeholder="Select department"
                                        initialLabel={asset?.department?.name}
                                    />
                                    <AsyncSelectField
                                        name="to_employee_id"
                                        label="To Employee"
                                        url="/api/employees"
                                        placeholder="Select employee"
                                        initialLabel={asset?.employee?.name}
                                    />
                                </div>
                            </div>
                        )}

                        <div className="space-y-4">
                            <h4 className="text-sm font-medium">
                                Reference & Notes
                            </h4>
                            <InputField
                                name="reference"
                                label="Reference / Document #"
                                placeholder="e.g. TRF-2024-001"
                            />
                            <TextareaField
                                name="notes"
                                label="Notes"
                                placeholder="Reason for movement..."
                            />
                        </div>
                    </div>
                )}
            </EntityForm>
        );
    },
);
