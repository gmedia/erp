'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { format } from 'date-fns';
import { memo, useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import { AssetMaintenanceFormData, assetMaintenanceFormSchema } from '@/utils/schemas';

interface AssetMaintenanceFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    item?: any | null;
    onSubmit: (data: AssetMaintenanceFormData) => void;
    isLoading?: boolean;
}

const getAssetMaintenanceFormDefaults = (item?: any | null): AssetMaintenanceFormData => {
    if (!item) {
        return {
            asset_id: '',
            maintenance_type: 'other',
            status: 'scheduled',
            scheduled_at: new Date(),
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
        scheduled_at: item.scheduled_at ? new Date(item.scheduled_at) : new Date(),
        performed_at: item.performed_at ? new Date(item.performed_at) : null,
        supplier_id: item.supplier_id ? String(item.supplier_id) : '',
        cost: item.cost ?? '0',
        notes: item.notes || '',
    };
};

export const AssetMaintenanceForm = memo<AssetMaintenanceFormProps>(function AssetMaintenanceForm({
    open,
    onOpenChange,
    item,
    onSubmit,
    isLoading = false,
}) {
    const defaultValues = useMemo(() => getAssetMaintenanceFormDefaults(item), [item]);

    type AssetMaintenanceFormInput = z.input<typeof assetMaintenanceFormSchema>;

    const form = useForm<AssetMaintenanceFormInput, any, AssetMaintenanceFormData>({
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
            scheduled_at: data.scheduled_at ? (format(data.scheduled_at, 'yyyy-MM-dd HH:mm:ss') as any) : null,
            performed_at: data.performed_at ? (format(data.performed_at, 'yyyy-MM-dd HH:mm:ss') as any) : null,
        });
    };

    const initialAssetLabel = item?.asset
        ? `${item.asset.asset_code || ''} ${item.asset.name || ''}`.trim()
        : undefined;

    return (
        <EntityForm<AssetMaintenanceFormData>
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={item ? 'Edit Asset Maintenance' : 'Add New Asset Maintenance'}
            submitLabel={item ? 'Update Maintenance' : 'Save Maintenance'}
            onSubmit={handleFormSubmit}
            isLoading={isLoading}
            className="sm:max-w-[650px]"
        >
            <div className="space-y-6">
                <AsyncSelectField
                    name="asset_id"
                    label="Asset"
                    url="/api/assets"
                    placeholder="Select asset"
                    initialLabel={initialAssetLabel}
                    labelFn={(a) => `${a.asset_code || ''} ${a.name || ''}`.trim()}
                />

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <SelectField
                        name="maintenance_type"
                        label="Maintenance Type"
                        options={[
                            { value: 'preventive', label: 'Preventive' },
                            { value: 'corrective', label: 'Corrective' },
                            { value: 'calibration', label: 'Calibration' },
                            { value: 'other', label: 'Other' },
                        ]}
                    />
                    <SelectField
                        name="status"
                        label="Status"
                        options={[
                            { value: 'scheduled', label: 'Scheduled' },
                            { value: 'in_progress', label: 'In Progress' },
                            { value: 'completed', label: 'Completed' },
                            { value: 'cancelled', label: 'Cancelled' },
                        ]}
                    />
                    <DatePickerField
                        name="scheduled_at"
                        label="Scheduled At"
                        placeholder="Pick a date"
                    />
                    {status === 'completed' && (
                        <DatePickerField
                            name="performed_at"
                            label="Performed At"
                            placeholder="Pick a date"
                        />
                    )}
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <AsyncSelectField
                        name="supplier_id"
                        label="Supplier"
                        url="/api/suppliers"
                        placeholder="Select supplier"
                        initialLabel={item?.supplier || undefined}
                    />
                    <InputField
                        name="cost"
                        label="Cost"
                        type="number"
                        placeholder="0"
                        min={0}
                    />
                </div>

                <TextareaField
                    name="notes"
                    label="Notes"
                    placeholder="Additional notes..."
                    rows={3}
                />
            </div>
        </EntityForm>
    );
});
