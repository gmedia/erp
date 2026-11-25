'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { format } from 'date-fns';
import { memo, useEffect, useMemo, useState } from 'react';
import { useForm } from 'react-hook-form';
import * as z from 'zod';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import { FileUploadInput } from '@/components/common/FileUploadInput';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import { useTranslation } from '@/contexts/i18n-context';
import { Asset, AssetMovement } from '@/types/asset';

import { toDateInputValue } from '@/utils/date-format';
import {
    AssetMovementFormData,
    assetMovementFormSchema,
} from '@/utils/schemas';

interface AssetMovementFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: AssetMovement | null; // For editing (passed by EntityCrudPage)
    asset?: Asset | null; // For creating from asset profile page
    onSubmit: (data: AssetMovementFormData | FormData) => void;
    isLoading?: boolean;
}

const toOptionalSelectValue = (value: string | number | null | undefined) => {
    if (typeof value === 'string' || typeof value === 'number') {
        return String(value);
    }

    return '';
};

type MovementFieldLabeler = (key: string, fallback: string) => string;

const getMovementTypeOptions = (fl: MovementFieldLabeler) => [
    {
        value: 'transfer',
        label: fl('movement_type_transfer', 'Transfer (Location Change)'),
    },
    {
        value: 'assign',
        label: fl('movement_type_assign', 'Assign (PIC Change)'),
    },
    { value: 'return', label: fl('movement_type_return', 'Return') },
    { value: 'dispose', label: fl('movement_type_dispose', 'Dispose') },
    {
        value: 'adjustment',
        label: fl('movement_type_adjustment', 'Adjustment'),
    },
];

const MOVEMENT_REFERENCE_PREFIX: Record<
    AssetMovementFormData['movement_type'],
    string
> = {
    transfer: 'TRF',
    assign: 'ASG',
    return: 'RTN',
    dispose: 'DSP',
    adjustment: 'ADJ',
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
            moved_at: toDateInputValue(item.moved_at ?? new Date()),
            to_branch_id: toOptionalSelectValue(item.to_branch_id),
            to_location_id: toOptionalSelectValue(item.to_location_id),
            to_department_id: toOptionalSelectValue(item.to_department_id),
            to_employee_id: toOptionalSelectValue(item.to_employee_id),
            reference: item.reference || '',
            notes: item.notes || '',
            sale_amount:
                item.sale_amount !== null && item.sale_amount !== undefined
                    ? String(item.sale_amount)
                    : '',
            sale_account_id: toOptionalSelectValue(item.sale_account_id),
        };
    }

    return {
        asset_id: toOptionalSelectValue(asset?.id),
        movement_type: 'transfer',
        moved_at: toDateInputValue(new Date()),
        to_branch_id: toOptionalSelectValue(asset?.branch_id),
        to_location_id: toOptionalSelectValue(asset?.asset_location_id),
        to_department_id: toOptionalSelectValue(asset?.department_id),
        to_employee_id: toOptionalSelectValue(asset?.employee_id),
        reference: '',
        notes: '',
        sale_amount: '',
        sale_account_id: '',
    };
};

export const AssetMovementForm = memo<AssetMovementFormProps>(
    function AssetMovementForm({
        open,
        onOpenChange,
        entity,
        asset,
        onSubmit,
        isLoading = false,
    }) {
        const { t } = useTranslation();
        const fl: MovementFieldLabeler = (key, fallback) => {
            const translated = t(`form_fields.asset_movements.${key}`);
            return translated === `form_fields.asset_movements.${key}`
                ? fallback
                : translated;
        };
        const defaultValues = useMemo(
            () => getAssetMovementFormDefaults(entity, asset),
            [entity, asset],
        );

        const form = useForm<z.input<typeof assetMovementFormSchema>>({
            resolver: zodResolver(assetMovementFormSchema),
            defaultValues,
        });

        const [documentFile, setDocumentFile] = useState<File | null>(null);
        const [pickedAssetStatus, setPickedAssetStatus] = useState<
            Asset['status'] | null
        >(null);
        const effectiveAssetStatus = asset?.status ?? pickedAssetStatus;

        const movementType = form.watch('movement_type');
        const referencePlaceholder = fl(
            'reference_placeholder_prefix',
            'e.g. {prefix}-{year}-001',
        )
            .replace(
                '{prefix}',
                MOVEMENT_REFERENCE_PREFIX[movementType] ?? 'TRF',
            )
            .replace('{year}', String(new Date().getFullYear()));
        const toBranchId = form.watch('to_branch_id');
        const saleAmountValue = Number(form.watch('sale_amount') ?? '');
        const hasSaleProceeds = saleAmountValue > 0;

        useEffect(() => {
            if (open) {
                form.reset(defaultValues);
                setDocumentFile(null);
            }
        }, [open, form, defaultValues]);

        const handleFormSubmit = (data: AssetMovementFormData) => {
            const formData = new FormData();

            formData.append('asset_id', data.asset_id);
            formData.append('movement_type', data.movement_type);
            formData.append(
                'moved_at',
                format(data.moved_at, 'yyyy-MM-dd HH:mm:ss'),
            );

            const optionalFields: Array<[string, string | undefined]> = [
                ['to_branch_id', data.to_branch_id],
                ['to_location_id', data.to_location_id],
                ['to_department_id', data.to_department_id],
                ['to_employee_id', data.to_employee_id],
                ['reference', data.reference],
                ['notes', data.notes],
            ];

            optionalFields.forEach(([key, value]) => {
                if (value) {
                    formData.append(key, value);
                }
            });

            // Hasil penjualan hanya untuk pelepasan (#442); nilai 0/kosong = tanpa hasil.
            const saleAmount = Number(data.sale_amount ?? '');
            if (data.movement_type === 'dispose' && saleAmount > 0) {
                formData.append('sale_amount', String(saleAmount));
                formData.append('sale_account_id', data.sale_account_id ?? '');
            }

            if (documentFile) {
                formData.append('document', documentFile);
            }

            onSubmit(formData);
        };

        const isEdit = !!entity;
        const allMovementTypeOptions = getMovementTypeOptions(fl);
        const movementTypeOptions =
            effectiveAssetStatus === 'lost' && !isEdit
                ? allMovementTypeOptions.filter(
                      (opt) => opt.value === 'dispose',
                  )
                : allMovementTypeOptions;
        let formTitle = fl('record_title', 'Record Asset Movement');

        if (isEdit) {
            formTitle = fl('edit_title', 'Edit Movement');
        } else if (asset) {
            formTitle = fl(
                'record_for_title',
                'Record Movement for {code}',
            ).replace('{code}', asset.asset_code);
        }

        return (
            <EntityForm<AssetMovementFormData>
                form={form}
                open={open}
                onOpenChange={onOpenChange}
                title={formTitle}
                submitLabel={
                    isEdit
                        ? fl('submit_update', 'Update Movement')
                        : fl('submit_record', 'Record Movement')
                }
                onSubmit={handleFormSubmit}
                isLoading={isLoading}
                className="sm:max-w-[600px]"
            >
                {open && (
                    <div className="space-y-6">
                        {!asset && !isEdit && (
                            <div className="grid grid-cols-1 gap-4">
                                <AsyncSelectField<Asset>
                                    name="asset_id"
                                    label={fl('asset', 'Asset')}
                                    url="/api/assets?exclude_status=disposed"
                                    placeholder={fl(
                                        'asset_placeholder',
                                        'Select asset',
                                    )}
                                    onItemSelect={(item) =>
                                        setPickedAssetStatus(item.status)
                                    }
                                />
                            </div>
                        )}

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <SelectField
                                name="movement_type"
                                label={fl('movement_type', 'Movement Type')}
                                disabled={isEdit}
                                options={movementTypeOptions}
                            />
                            <DatePickerField
                                name="moved_at"
                                label={fl('moved_at', 'Movement Date')}
                            />
                        </div>

                        {!isEdit && <hr />}

                        {movementType === 'transfer' && !isEdit && (
                            <div className="space-y-4">
                                <h4 className="text-sm font-medium">
                                    {fl(
                                        'destination_title',
                                        'Destination Details',
                                    )}
                                </h4>
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <AsyncSelectField
                                        name="to_branch_id"
                                        label={fl('to_branch', 'To Branch')}
                                        url="/api/branches"
                                        placeholder={fl(
                                            'to_branch_placeholder',
                                            'Select destination branch',
                                        )}
                                        initialLabel={asset?.branch?.name}
                                    />
                                    <AsyncSelectField
                                        name="to_location_id"
                                        label={fl('to_location', 'To Location')}
                                        url={
                                            toBranchId
                                                ? `/api/asset-locations?branch_id=${toBranchId}`
                                                : '/api/asset-locations'
                                        }
                                        placeholder={fl(
                                            'to_location_placeholder',
                                            'Select destination location',
                                        )}
                                        key={`location-select-${toBranchId}`}
                                        initialLabel={asset?.location?.name}
                                    />
                                </div>
                            </div>
                        )}

                        {movementType === 'assign' && !isEdit && (
                            <div className="space-y-4">
                                <h4 className="text-sm font-medium">
                                    {fl(
                                        'assignment_title',
                                        'Assignment Details',
                                    )}
                                </h4>
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <AsyncSelectField
                                        name="to_department_id"
                                        label={fl(
                                            'to_department',
                                            'To Department',
                                        )}
                                        url="/api/departments"
                                        placeholder={fl(
                                            'to_department_placeholder',
                                            'Select department',
                                        )}
                                        initialLabel={asset?.department?.name}
                                    />
                                    <AsyncSelectField
                                        name="to_employee_id"
                                        label={fl('to_employee', 'To Employee')}
                                        url="/api/employees"
                                        placeholder={fl(
                                            'to_employee_placeholder',
                                            'Select employee',
                                        )}
                                        initialLabel={asset?.employee?.name}
                                    />
                                </div>
                            </div>
                        )}

                        {movementType === 'dispose' && !isEdit && (
                            <div className="space-y-4">
                                <h4 className="text-sm font-medium">
                                    {t('assets.disposal.saleSection')}
                                </h4>
                                <p className="text-xs text-muted-foreground">
                                    {t('assets.disposal.saleHint')}
                                </p>
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <InputField
                                        name="sale_amount"
                                        label={t('assets.disposal.saleAmount')}
                                        type="number"
                                        placeholder="0"
                                    />
                                    {hasSaleProceeds && (
                                        <AsyncSelectField
                                            name="sale_account_id"
                                            label={t(
                                                'assets.disposal.receiptAccount',
                                            )}
                                            url="/api/accounts?is_active=1&has_children=0"
                                            placeholder={t(
                                                'assets.disposal.receiptPlaceholder',
                                            )}
                                        />
                                    )}
                                </div>
                            </div>
                        )}

                        <div className="space-y-4">
                            <h4 className="text-sm font-medium">
                                {fl('reference_title', 'Reference & Notes')}
                            </h4>
                            <InputField
                                name="reference"
                                label={fl(
                                    'reference',
                                    'Reference / Document #',
                                )}
                                placeholder={referencePlaceholder}
                            />
                            <TextareaField
                                name="notes"
                                label={fl('notes', 'Notes')}
                                placeholder={fl(
                                    'notes_placeholder',
                                    'Reason for movement...',
                                )}
                            />
                            <div className="space-y-2">
                                <label
                                    htmlFor="movement-document"
                                    className="text-sm font-medium"
                                >
                                    {fl('document', 'Supporting Document')}
                                </label>
                                <FileUploadInput
                                    id="movement-document"
                                    value={documentFile}
                                    onChange={setDocumentFile}
                                    accept=".pdf,.jpg,.jpeg,.png"
                                />
                                {entity?.document_name && (
                                    <p className="text-xs text-muted-foreground">
                                        {fl(
                                            'document_current',
                                            'Current: {name}',
                                        ).replace(
                                            '{name}',
                                            entity.document_name,
                                        )}
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </EntityForm>
        );
    },
);
