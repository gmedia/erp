'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo, useRef } from 'react';
import { useForm } from 'react-hook-form';
import { format } from 'date-fns';
import { z } from 'zod';

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
        asset_category_id: asset.category?.id ? String(asset.category.id) : (asset.asset_category_id ? String(asset.asset_category_id) : ''),
        asset_model_id: asset.model?.id ? String(asset.model.id) : (asset.asset_model_id ? String(asset.asset_model_id) : ''),
        serial_number: asset.serial_number || '',
        barcode: asset.barcode || '',
        branch_id: asset.branch?.id ? String(asset.branch.id) : (asset.branch_id ? String(asset.branch_id) : ''),
        asset_location_id: asset.location?.id ? String(asset.location.id) : (asset.asset_location_id ? String(asset.asset_location_id) : ''),
        department_id: asset.department?.id ? String(asset.department.id) : (asset.department_id ? String(asset.department_id) : ''),
        employee_id: asset.employee?.id ? String(asset.employee.id) : (asset.employee_id ? String(asset.employee_id) : ''),
        supplier_id: asset.supplier?.id ? String(asset.supplier.id) : (asset.supplier_id ? String(asset.supplier_id) : ''),
        purchase_date: new Date(asset.purchase_date),
        purchase_cost: asset.purchase_cost || '',
        currency: asset.currency || 'IDR',
        warranty_end_date: asset.warranty_end_date ? new Date(asset.warranty_end_date) : null,
        status: asset.status,
        condition: asset.condition,
        notes: asset.notes || '',
        depreciation_method: asset.depreciation_method || 'straight_line',
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

    type AssetFormInput = z.input<typeof assetFormSchema>;

    const form = useForm<AssetFormInput, any, AssetFormData>({
        resolver: zodResolver(assetFormSchema),
        defaultValues,
    });

    const categoryId = form.watch('asset_category_id');
    const branchId = form.watch('branch_id');

    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);

    // Reset model when category changes, but NOT on initial load
    const prevCategoryIdRef = useRef(categoryId);
    useEffect(() => {
        if (prevCategoryIdRef.current !== categoryId) {
            // Only reset if it's not the initial load from props
            const isInitialPropMatch = asset && (
                String(asset.asset_category_id) === String(categoryId) || 
                String(asset.category?.id) === String(categoryId)
            );
            
            if (!isInitialPropMatch) {
                const currentModelId = form.getValues('asset_model_id');
                if (currentModelId) {
                    form.setValue('asset_model_id', '');
                }
            }
        }
        prevCategoryIdRef.current = categoryId;
    }, [categoryId, asset, form]);

    // Reset location when branch changes, but NOT on initial load
    const prevBranchIdRef = useRef(branchId);
    useEffect(() => {
        if (prevBranchIdRef.current !== branchId) {
            // Only reset if it's not the initial load from props
            const isInitialPropMatch = asset && (
                String(asset.branch_id) === String(branchId) || 
                String(asset.branch?.id) === String(branchId)
            );
            
            if (!isInitialPropMatch) {
                const currentLocationId = form.getValues('asset_location_id');
                if (currentLocationId) {
                    form.setValue('asset_location_id', '');
                }
            }
        }
        prevBranchIdRef.current = branchId;
    }, [branchId, asset, form]);

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
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <InputField name="asset_code" label="Asset Code" placeholder="FA-000001" />
                        <InputField name="name" label="Asset Name" placeholder="Laptop Dell Latitude" />
                        <AsyncSelectField
                            name="asset_category_id"
                            label="Category"
                            url="/api/asset-categories"
                            placeholder="Select a category"
                            initialLabel={asset?.category?.name}
                        />
                        <AsyncSelectField
                            name="asset_model_id"
                            label="Model"
                            url={categoryId ? `/api/asset-models?asset_category_id=${categoryId}` : '/api/asset-models'}
                            placeholder="Select a model"
                            key={`model-select-${categoryId}`} // More stable key
                            initialLabel={asset?.model?.name || asset?.model?.model_name}
                            labelFn={(item) => item.model_name || item.name}
                        />
                        <InputField name="serial_number" label="Serial Number" placeholder="SN-123456" />
                        <InputField name="barcode" label="Barcode" placeholder="BC-123456" />
                    </div>
                </div>
                <hr />
                <div>
                    <h3 className="text-lg font-medium mb-4">Ownership & Location</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <AsyncSelectField
                            name="branch_id"
                            label="Branch"
                            url="/api/branches"
                            placeholder="Select a branch"
                            initialLabel={asset?.branch?.name}
                        />
                        <AsyncSelectField
                            name="asset_location_id"
                            label="Location"
                            url={branchId ? `/api/asset-locations?branch_id=${branchId}` : '/api/asset-locations'}
                            placeholder="Select a location"
                            key={`location-select-${branchId}`}
                            initialLabel={asset?.location?.name}
                        />
                        <AsyncSelectField
                            name="department_id"
                            label="Department"
                            url="/api/departments"
                            placeholder="Select a department"
                            initialLabel={asset?.department?.name}
                        />
                        <AsyncSelectField
                            name="employee_id"
                            label="Employee"
                            url="/api/employees"
                            placeholder="Select an employee"
                            initialLabel={asset?.employee?.name}
                        />
                        <AsyncSelectField
                            name="supplier_id"
                            label="Supplier"
                            url="/api/suppliers"
                            placeholder="Select a supplier"
                            initialLabel={asset?.supplier?.name}
                        />
                    </div>
                </div>
                <hr />
                <div>
                    <h3 className="text-lg font-medium mb-4">Financial Details</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <DatePickerField name="purchase_date" label="Purchase Date" placeholder="Pick a date" />
                        <InputField name="purchase_cost" label="Purchase Cost" type="number" placeholder="0" />
                        <InputField name="currency" label="Currency" placeholder="IDR" />
                        <DatePickerField name="warranty_end_date" label="Warranty End Date" placeholder="Pick a date" />
                    </div>
                </div>
                <hr />
                <div>
                    <h3 className="text-lg font-medium mb-4">Status & Notes</h3>
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
                </div>
            </div>
        </EntityForm>
    );
});
