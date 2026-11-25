'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { format } from 'date-fns';
import { memo, useEffect, useMemo, useRef } from 'react';
import { useForm, type UseFormReturn } from 'react-hook-form';
import { z } from 'zod';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import { StatusSelectField } from '@/components/common/StatusSelectField';
import { TextareaField } from '@/components/common/TextareaField';
import { useTranslation } from '@/contexts/i18n-context';

import { Asset } from '@/types/asset';
import { toDateInputValue } from '@/utils/date-format';
import { AssetFormData, assetFormSchema } from '@/utils/schemas';

interface AssetFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: Asset | null;
    onSubmit: (data: AssetFormData) => void;
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

const getAssetModelOptionLabel = (option: Record<string, unknown>) => {
    const modelName = option.model_name;

    if (typeof modelName === 'string' || typeof modelName === 'number') {
        return String(modelName);
    }

    const name = option.name;

    if (typeof name === 'string' || typeof name === 'number') {
        return String(name);
    }

    return '';
};

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
            purchase_date: toDateInputValue(new Date()),
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
        asset_category_id: toOptionalSelectValue(
            asset.category?.id,
            asset.asset_category_id,
        ),
        asset_model_id: toOptionalSelectValue(
            asset.model?.id,
            asset.asset_model_id,
        ),
        serial_number: asset.serial_number || '',
        barcode: asset.barcode || '',
        branch_id: toOptionalSelectValue(asset.branch?.id, asset.branch_id),
        asset_location_id: toOptionalSelectValue(
            asset.location?.id,
            asset.asset_location_id,
        ),
        department_id: toOptionalSelectValue(
            asset.department?.id,
            asset.department_id,
        ),
        employee_id: toOptionalSelectValue(
            asset.employee?.id,
            asset.employee_id,
        ),
        supplier_id: toOptionalSelectValue(
            asset.supplier?.id,
            asset.supplier_id,
        ),
        purchase_date: toDateInputValue(asset.purchase_date),
        purchase_cost: asset.purchase_cost || '',
        currency: asset.currency || 'IDR',
        warranty_end_date: asset.warranty_end_date
            ? toDateInputValue(asset.warranty_end_date)
            : null,
        status: asset.status,
        condition: asset.condition,
        notes: asset.notes || '',
        depreciation_method: asset.depreciation_method || 'straight_line',
        depreciation_start_date: asset.depreciation_start_date
            ? toDateInputValue(asset.depreciation_start_date)
            : null,
        useful_life_months: asset.useful_life_months
            ? String(asset.useful_life_months)
            : '',
        salvage_value: asset.salvage_value || '',
        depreciation_expense_account_id: asset.depreciation_expense_account_id
            ? String(asset.depreciation_expense_account_id)
            : '',
        accumulated_depr_account_id: asset.accumulated_depr_account_id
            ? String(asset.accumulated_depr_account_id)
            : '',
    };
};

export const AssetForm = memo<AssetFormProps>(function AssetForm({
    open,
    onOpenChange,
    entity,
    onSubmit,
    isLoading = false,
}) {
    const { t } = useTranslation();
    const fl = (key: string, fallback: string) => {
        const translated = t(`form_fields.assets.${key}`);
        return translated === `form_fields.assets.${key}`
            ? fallback
            : translated;
    };
    const defaultValues = useMemo(() => getAssetFormDefaults(entity), [entity]);

    type AssetFormInput = z.input<typeof assetFormSchema>;

    const form = useForm<AssetFormInput, unknown, AssetFormData>({
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
            const isInitialPropMatch =
                entity &&
                (String(entity.asset_category_id) === String(categoryId) ||
                    String(entity.category?.id) === String(categoryId));

            if (!isInitialPropMatch) {
                const currentModelId = form.getValues('asset_model_id');
                if (currentModelId) {
                    form.setValue('asset_model_id', '');
                }
            }
        }
        prevCategoryIdRef.current = categoryId;
    }, [categoryId, entity, form]);

    // Reset location when branch changes, but NOT on initial load
    const prevBranchIdRef = useRef(branchId);
    useEffect(() => {
        if (prevBranchIdRef.current !== branchId) {
            // Only reset if it's not the initial load from props
            const isInitialPropMatch =
                entity &&
                (String(entity.branch_id) === String(branchId) ||
                    String(entity.branch?.id) === String(branchId));

            if (!isInitialPropMatch) {
                const currentLocationId = form.getValues('asset_location_id');
                if (currentLocationId) {
                    form.setValue('asset_location_id', '');
                }
            }
        }
        prevBranchIdRef.current = branchId;
    }, [branchId, entity, form]);

    const handleFormSubmit = (data: AssetFormData) => {
        onSubmit({
            ...data,
            purchase_date: format(data.purchase_date, 'yyyy-MM-dd'),
            warranty_end_date: data.warranty_end_date
                ? format(data.warranty_end_date, 'yyyy-MM-dd')
                : null,
            depreciation_start_date: data.depreciation_start_date
                ? format(data.depreciation_start_date, 'yyyy-MM-dd')
                : null,
        });
    };

    return (
        <EntityForm<AssetFormData>
            form={
                form as unknown as UseFormReturn<
                    AssetFormData,
                    unknown,
                    AssetFormData
                >
            }
            open={open}
            onOpenChange={onOpenChange}
            title={
                entity
                    ? fl('edit_title', 'Edit Asset')
                    : fl('add_title', 'Add New Asset')
            }
            onSubmit={handleFormSubmit}
            isLoading={isLoading}
            className="sm:max-w-[700px]"
        >
            <div className="space-y-6">
                <div>
                    <h3 className="mb-4 text-lg font-medium">
                        {fl('section_basic', 'Basic Information')}
                    </h3>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <InputField
                            name="asset_code"
                            label={fl('asset_code', 'Asset Code')}
                            placeholder={fl(
                                'asset_code_placeholder',
                                'FA-000001',
                            )}
                        />
                        <InputField
                            name="name"
                            label={fl('name', 'Asset Name')}
                            placeholder={fl(
                                'name_placeholder',
                                'Laptop Dell Latitude',
                            )}
                        />
                        <AsyncSelectField
                            name="asset_category_id"
                            label={fl('category', 'Category')}
                            url="/api/asset-categories"
                            placeholder={fl(
                                'category_placeholder',
                                'Select a category',
                            )}
                            initialLabel={entity?.category?.name}
                        />
                        <AsyncSelectField
                            name="asset_model_id"
                            label={fl('model', 'Model')}
                            url={
                                categoryId
                                    ? `/api/asset-models?asset_category_id=${categoryId}`
                                    : '/api/asset-models'
                            }
                            placeholder={fl(
                                'model_placeholder',
                                'Select a model',
                            )}
                            key={`model-select-${categoryId}`} // More stable key
                            initialLabel={
                                entity?.model?.name || entity?.model?.model_name
                            }
                            labelFn={getAssetModelOptionLabel}
                        />
                        <InputField
                            name="serial_number"
                            label={fl('serial_number', 'Serial Number')}
                            placeholder={fl(
                                'serial_number_placeholder',
                                'SN-123456',
                            )}
                        />
                        <InputField
                            name="barcode"
                            label={fl('barcode', 'Barcode')}
                            placeholder={fl('barcode_placeholder', 'BC-123456')}
                        />
                    </div>
                </div>
                <hr />
                <div>
                    <h3 className="mb-4 text-lg font-medium">
                        {fl('section_ownership', 'Ownership & Location')}
                    </h3>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <AsyncSelectField
                            name="branch_id"
                            label={fl('branch', 'Branch')}
                            url="/api/branches"
                            placeholder={fl(
                                'branch_placeholder',
                                'Select a branch',
                            )}
                            initialLabel={entity?.branch?.name}
                        />
                        <AsyncSelectField
                            name="asset_location_id"
                            label={fl('location', 'Location')}
                            url={
                                branchId
                                    ? `/api/asset-locations?branch_id=${branchId}`
                                    : '/api/asset-locations'
                            }
                            placeholder={fl(
                                'location_placeholder',
                                'Select a location',
                            )}
                            key={`location-select-${branchId}`}
                            initialLabel={entity?.location?.name}
                        />
                        <AsyncSelectField
                            name="department_id"
                            label={fl('department', 'Department')}
                            url="/api/departments"
                            placeholder={fl(
                                'department_placeholder',
                                'Select a department',
                            )}
                            initialLabel={entity?.department?.name}
                        />
                        <AsyncSelectField
                            name="employee_id"
                            label={fl('employee', 'Employee')}
                            url="/api/employees"
                            placeholder={fl(
                                'employee_placeholder',
                                'Select an employee',
                            )}
                            initialLabel={entity?.employee?.name}
                        />
                        <AsyncSelectField
                            name="supplier_id"
                            label={fl('supplier', 'Supplier')}
                            url="/api/suppliers"
                            placeholder={fl(
                                'supplier_placeholder',
                                'Select a supplier',
                            )}
                            initialLabel={entity?.supplier?.name}
                        />
                    </div>
                </div>
                <hr />
                <div>
                    <h3 className="mb-4 text-lg font-medium">
                        {fl('section_financial', 'Financial Details')}
                    </h3>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <DatePickerField
                            name="purchase_date"
                            label={fl('purchase_date', 'Purchase Date')}
                            placeholder={fl(
                                'purchase_date_placeholder',
                                'Pick a date',
                            )}
                        />
                        <InputField
                            name="purchase_cost"
                            label={fl('purchase_cost', 'Purchase Cost')}
                            type="number"
                            placeholder={fl('purchase_cost_placeholder', '0')}
                        />
                        <DatePickerField
                            name="warranty_end_date"
                            label={fl('warranty_end_date', 'Warranty End Date')}
                            placeholder={fl(
                                'warranty_end_date_placeholder',
                                'Pick a date',
                            )}
                        />
                    </div>
                </div>
                <hr />
                <div>
                    <h3 className="mb-4 text-lg font-medium">
                        {fl('section_status', 'Status & Notes')}
                    </h3>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <StatusSelectField
                            fl={fl}
                            label={fl('status', 'Status')}
                            placeholder={fl(
                                'status_placeholder',
                                'Select status',
                            )}
                            statuses={[
                                ['draft', 'Draft'],
                                ['active', 'Active'],
                                ['maintenance', 'Maintenance'],
                                ['disposed', 'Disposed'],
                                ['lost', 'Lost'],
                            ]}
                        />
                        <SelectField
                            name="condition"
                            label={fl('condition', 'Condition')}
                            options={[
                                {
                                    value: 'good',
                                    label: fl('condition_good', 'Good'),
                                },
                                {
                                    value: 'needs_repair',
                                    label: fl(
                                        'condition_needs_repair',
                                        'Needs Repair',
                                    ),
                                },
                                {
                                    value: 'damaged',
                                    label: fl('condition_damaged', 'Damaged'),
                                },
                            ]}
                            placeholder={fl(
                                'condition_placeholder',
                                'Select condition',
                            )}
                        />
                        <div className="md:col-span-2">
                            <TextareaField
                                name="notes"
                                label={fl('notes', 'Notes')}
                                placeholder={fl(
                                    'notes_placeholder',
                                    'Additional notes...',
                                )}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </EntityForm>
    );
});
