'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';
import { format } from 'date-fns';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';

import { Asset } from '@/types/asset';
import { AssetFormData, assetFormSchema } from '@/utils/schemas';

interface AssetFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    asset?: Asset | null;
    onSubmit: (data: AssetFormData) => void;
    isLoading?: boolean;
}

const renderBasicInfoSection = () => (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <InputField name="asset_code" label="Asset Code" placeholder="FA-000001" />
        <InputField name="name" label="Asset Name" placeholder="Laptop Dell Latitude" />
        <AsyncSelectField
            name="asset_category_id"
            label="Category"
            url="/api/asset-categories"
            placeholder="Select a category"
        />
        <AsyncSelectField
            name="asset_model_id"
            label="Model"
            url="/api/asset-models"
            placeholder="Select a model"
        />
        <InputField name="serial_number" label="Serial Number" placeholder="SN-123456" />
        <InputField name="barcode" label="Barcode" placeholder="BC-123456" />
    </div>
);

const renderOwnershipSection = () => (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <AsyncSelectField
            name="branch_id"
            label="Branch"
            url="/api/branches"
            placeholder="Select a branch"
        />
        <AsyncSelectField
            name="asset_location_id"
            label="Location"
            url="/api/asset-locations"
            placeholder="Select a location"
        />
        <AsyncSelectField
            name="department_id"
            label="Department"
            url="/api/departments"
            placeholder="Select a department"
        />
        <AsyncSelectField
            name="employee_id"
            label="Employee"
            url="/api/employees"
            placeholder="Select an employee"
        />
        <AsyncSelectField
            name="supplier_id"
            label="Supplier"
            url="/api/suppliers"
            placeholder="Select a supplier"
        />
    </div>
);

const renderFinancialSection = () => (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <DatePickerField name="purchase_date" label="Purchase Date" placeholder="Pick a date" />
        <InputField name="purchase_cost" label="Purchase Cost" type="number" placeholder="0" />
        <InputField name="currency" label="Currency" placeholder="IDR" defaultValue="IDR" />
        <DatePickerField name="warranty_end_date" label="Warranty End Date" placeholder="Pick a date" />
    </div>
);

const renderStatusSection = () => (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <SelectField
            name="status"
            label="Status"
            options={[
                { value: 'draft', label: 'Draft' },
                { value: 'active', label: 'Active' },
                { value: 'maintenance', label: 'Maintenance' },
                { value: 'disposed', label: 'Disposed' },
                { value: 'lost', label: 'Lost' },
            ]}
            placeholder="Select status"
        />
        <SelectField
            name="condition"
            label="Condition"
            options={[
                { value: 'good', label: 'Good' },
                { value: 'needs_repair', label: 'Needs Repair' },
                { value: 'damaged', label: 'Damaged' },
            ]}
            placeholder="Select condition"
        />
        <div className="md:col-span-2">
            <TextareaField name="notes" label="Notes" placeholder="Additional notes..." />
        </div>
    </div>
);

const getAssetFormDefaults = (asset?: Asset | null): AssetFormData => {
    if (!asset) {
        return {
            asset_code: '',
            name: '',
            asset_category_id: '',
            asset_model_id: '',
            serial_number: '',
            barcode: '',
            branch_id: '',
            asset_location_id: '',
            department_id: '',
            employee_id: '',
            supplier_id: '',
            purchase_date: new Date(),
            purchase_cost: '',
            currency: 'IDR',
            warranty_end_date: null,
            status: 'draft',
            condition: 'good',
            notes: '',
            depreciation_method: 'straight_line',
            depreciation_start_date: null,
            useful_life_months: '',
            salvage_value: '',
            depreciation_expense_account_id: '',
            accumulated_depr_account_id: '',
        };
    }

    return {
        asset_code: asset.asset_code,
        name: asset.name,
        asset_category_id: String(asset.asset_category_id),
        asset_model_id: asset.asset_model_id ? String(asset.asset_model_id) : '',
        serial_number: asset.serial_number || '',
        barcode: asset.barcode || '',
        branch_id: String(asset.branch_id),
        asset_location_id: asset.asset_location_id ? String(asset.asset_location_id) : '',
        department_id: asset.department_id ? String(asset.department_id) : '',
        employee_id: asset.employee_id ? String(asset.employee_id) : '',
        supplier_id: asset.supplier_id ? String(asset.supplier_id) : '',
        purchase_date: new Date(asset.purchase_date),
        purchase_cost: asset.purchase_cost || '',
        currency: asset.currency || 'IDR',
        warranty_end_date: asset.warranty_end_date ? new Date(asset.warranty_end_date) : null,
        status: asset.status,
        condition: asset.condition,
        notes: asset.notes || '',
        depreciation_method: asset.depreciation_method,
        depreciation_start_date: asset.depreciation_start_date ? new Date(asset.depreciation_start_date) : null,
        useful_life_months: asset.useful_life_months ? String(asset.useful_life_months) : '',
        salvage_value: asset.salvage_value || '',
        depreciation_expense_account_id: asset.depreciation_expense_account_id ? String(asset.depreciation_expense_account_id) : '',
        accumulated_depr_account_id: asset.accumulated_depr_account_id ? String(asset.accumulated_depr_account_id) : '',
    };
};

export const AssetForm = memo<AssetFormProps>(function AssetForm({
    open,
    onOpenChange,
    asset,
    onSubmit,
    isLoading = false,
}) {
    const defaultValues = useMemo(() => getAssetFormDefaults(asset), [asset]);

    const form = useForm<AssetFormData>({
        resolver: zodResolver(assetFormSchema),
        defaultValues,
    });

    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);

    const handleFormSubmit = (data: AssetFormData) => {
        onSubmit({
            ...data,
            purchase_date: format(data.purchase_date, 'yyyy-MM-dd') as any,
            warranty_end_date: data.warranty_end_date ? format(data.warranty_end_date, 'yyyy-MM-dd') as any : null,
            depreciation_start_date: data.depreciation_start_date ? format(data.depreciation_start_date, 'yyyy-MM-dd') as any : null,
        });
    };

    return (
        <EntityForm<AssetFormData>
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={asset ? 'Edit Asset' : 'Add New Asset'}
            onSubmit={handleFormSubmit}
            isLoading={isLoading}
            className="sm:max-w-[700px]"
        >
            <div className="space-y-6">
                <div>
                    <h3 className="text-lg font-medium mb-4">Basic Information</h3>
                    {renderBasicInfoSection()}
                </div>
                <hr />
                <div>
                    <h3 className="text-lg font-medium mb-4">Ownership & Location</h3>
                    {renderOwnershipSection()}
                </div>
                <hr />
                <div>
                    <h3 className="text-lg font-medium mb-4">Financial Details</h3>
                    {renderFinancialSection()}
                </div>
                <hr />
                <div>
                    <h3 className="text-lg font-medium mb-4">Status & Notes</h3>
                    {renderStatusSection()}
                </div>
            </div>
        </EntityForm>
    );
});
