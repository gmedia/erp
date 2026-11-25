'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { format } from 'date-fns';
import { memo, useEffect, useMemo } from 'react';
import { useForm, type UseFormReturn } from 'react-hook-form';
import { z } from 'zod';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import { useTranslation } from '@/contexts/i18n-context';
import { type AssetMaintenance } from '@/types/asset-maintenance';
import { toDateInputValue } from '@/utils/date-format';
import {
    AssetMaintenanceFormData,
    assetMaintenanceFormSchema,
} from '@/utils/schemas';

interface AssetMaintenanceFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: AssetMaintenance | null;
    onSubmit: (data: AssetMaintenanceFormData) => void;
    isLoading?: boolean;
}

const getAssetMaintenanceFormDefaults = (
    item?: AssetMaintenance | null,
): AssetMaintenanceFormData => {
    if (!item) {
        return {
            asset_id: '',
            maintenance_type: 'other',
            status: 'scheduled',
            scheduled_at: toDateInputValue(new Date()),
            performed_at: null,
            supplier_id: '',
            cost: '0',
            notes: '',
        };
    }

    return {
        asset_id: item.asset_id ? String(item.asset_id) : '',
        maintenance_type: item.maintenance_type || 'other',
        status: item.status || 'scheduled',
        scheduled_at: item.scheduled_at
            ? toDateInputValue(item.scheduled_at)
            : toDateInputValue(new Date()),
        performed_at: item.performed_at
            ? toDateInputValue(item.performed_at)
            : null,
        supplier_id: item.supplier_id ? String(item.supplier_id) : '',
        cost: item.cost ?? '0',
        notes: item.notes || '',
    };
};

export const AssetMaintenanceForm = memo<AssetMaintenanceFormProps>(
    function AssetMaintenanceForm({
        open,
        onOpenChange,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const { t } = useTranslation();
        const fl = (key: string, fallback: string) => {
            const translated = t(`form_fields.asset_maintenances.${key}`);
            return translated === `form_fields.asset_maintenances.${key}`
                ? fallback
                : translated;
        };
        const defaultValues = useMemo(
            () => getAssetMaintenanceFormDefaults(entity),
            [entity],
        );

        type AssetMaintenanceFormInput = z.input<
            typeof assetMaintenanceFormSchema
        >;

        const form = useForm<
            AssetMaintenanceFormInput,
            unknown,
            AssetMaintenanceFormData
        >({
            resolver: zodResolver(assetMaintenanceFormSchema),
            defaultValues,
        });

        const status = form.watch('status');

        useEffect(() => {
            if (open) {
                form.reset(defaultValues);
            }
        }, [open, form, defaultValues]);

        const handleFormSubmit = (data: AssetMaintenanceFormData) => {
            onSubmit({
                ...data,
                scheduled_at: data.scheduled_at
                    ? format(data.scheduled_at, 'yyyy-MM-dd HH:mm:ss')
                    : '',
                performed_at: data.performed_at
                    ? format(data.performed_at, 'yyyy-MM-dd HH:mm:ss')
                    : null,
            });
        };

        const initialAssetLabel = entity?.asset
            ? `${entity.asset.asset_code || ''} ${entity.asset.name || ''}`.trim()
            : undefined;

        return (
            <EntityForm<AssetMaintenanceFormData>
                form={
                    form as unknown as UseFormReturn<
                        AssetMaintenanceFormData,
                        unknown,
                        AssetMaintenanceFormData
                    >
                }
                open={open}
                onOpenChange={onOpenChange}
                title={
                    entity
                        ? fl('edit_title', 'Edit Asset Maintenance')
                        : fl('add_title', 'Add New Asset Maintenance')
                }
                submitLabel={
                    entity
                        ? fl('submit_update', 'Update Maintenance')
                        : fl('submit_save', 'Save Maintenance')
                }
                onSubmit={handleFormSubmit}
                isLoading={isLoading}
                className="sm:max-w-[650px]"
            >
                <div className="space-y-6">
                    <AsyncSelectField
                        name="asset_id"
                        label={fl('asset', 'Asset')}
                        url="/api/assets"
                        placeholder={fl('asset_placeholder', 'Select asset')}
                        initialLabel={initialAssetLabel}
                        labelFn={(a: Record<string, unknown>) =>
                            `${(a.asset_code as string) || ''} ${(a.name as string) || ''}`.trim()
                        }
                    />

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <SelectField
                            name="maintenance_type"
                            label={fl('maintenance_type', 'Maintenance Type')}
                            options={[
                                {
                                    value: 'preventive',
                                    label: fl(
                                        'maintenance_type_preventive',
                                        'Preventive',
                                    ),
                                },
                                {
                                    value: 'corrective',
                                    label: fl(
                                        'maintenance_type_corrective',
                                        'Corrective',
                                    ),
                                },
                                {
                                    value: 'calibration',
                                    label: fl(
                                        'maintenance_type_calibration',
                                        'Calibration',
                                    ),
                                },
                                {
                                    value: 'other',
                                    label: fl(
                                        'maintenance_type_other',
                                        'Other',
                                    ),
                                },
                            ]}
                        />
                        <SelectField
                            name="status"
                            label={fl('status', 'Status')}
                            options={[
                                {
                                    value: 'scheduled',
                                    label: fl('status_scheduled', 'Scheduled'),
                                },
                                {
                                    value: 'in_progress',
                                    label: fl(
                                        'status_in_progress',
                                        'In Progress',
                                    ),
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
                        <DatePickerField
                            name="scheduled_at"
                            label={fl('scheduled_at', 'Scheduled At')}
                            placeholder={fl('date_placeholder', 'Pick a date')}
                        />
                        {status === 'completed' && (
                            <DatePickerField
                                name="performed_at"
                                label={fl('performed_at', 'Performed At')}
                                placeholder={fl(
                                    'date_placeholder',
                                    'Pick a date',
                                )}
                            />
                        )}
                    </div>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <AsyncSelectField
                            name="supplier_id"
                            label={fl('supplier', 'Supplier')}
                            url="/api/suppliers"
                            placeholder={fl(
                                'supplier_placeholder',
                                'Select supplier',
                            )}
                            initialLabel={entity?.supplier || undefined}
                        />
                        <InputField
                            name="cost"
                            label={fl('cost', 'Cost')}
                            type="number"
                            placeholder={fl('cost_placeholder', '0')}
                            min={0}
                        />
                    </div>

                    <TextareaField
                        name="notes"
                        label={fl('notes', 'Notes')}
                        placeholder={fl(
                            'notes_placeholder',
                            'Additional notes...',
                        )}
                        rows={3}
                    />
                </div>
            </EntityForm>
        );
    },
);
