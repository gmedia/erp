'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { useMemo } from 'react';
import { type Resolver, useForm } from 'react-hook-form';
import { z } from 'zod';

import { InputField } from '@/components/common/InputField';
import {
    ItemFormDialogShell,
    ItemNotesField,
    ItemProductUnitFields,
} from '@/components/common/ItemFormDialog';
import { useTranslation } from '@/contexts/i18n-context';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import {
    type StockTransferFormData,
    stockTransferQuantityReceivedSchema,
} from '@/utils/schemas';

const stockTransferItemSchema = z.object({
    product_id: z.string().min(1, { message: 'Product is required.' }),
    product_label: z.string().optional(),
    unit_id: z.string().min(1, { message: 'Unit is required.' }),
    unit_label: z.string().optional(),
    quantity: z.coerce
        .number()
        .gt(0, { message: 'Quantity must be greater than 0.' }),
    quantity_received: stockTransferQuantityReceivedSchema,
    unit_cost: z.coerce.number().min(0).optional().default(0),
    notes: z.string().optional(),
});

type StockTransferItemFormData = StockTransferFormData['items'][number];

interface StockTransferItemFormDialogProps {
    readonly open: boolean;
    readonly onOpenChange: (open: boolean) => void;
    readonly item: StockTransferItemFormData | null;
    readonly onSave: (data: StockTransferItemFormData) => void;
    readonly locked?: boolean;
}

export function StockTransferItemFormDialog({
    open,
    onOpenChange,
    item,
    onSave,
    locked = false,
}: Readonly<StockTransferItemFormDialogProps>) {
    const { t } = useTranslation();
    const defaultValues = useMemo<StockTransferItemFormData>(() => {
        if (!item) {
            return {
                product_id: '',
                product_label: '',
                unit_id: '',
                unit_label: '',
                quantity: 1,
                quantity_received: null,
                unit_cost: 0,
                notes: '',
            };
        }

        return {
            product_id: item.product_id || '',
            product_label: item.product_label || '',
            unit_id: item.unit_id || '',
            unit_label: item.unit_label || '',
            quantity: Number(item.quantity || 0),
            quantity_received:
                item.quantity_received === null ||
                item.quantity_received === undefined
                    ? null
                    : Number(item.quantity_received),
            unit_cost: Number(item.unit_cost || 0),
            notes: item.notes || '',
        };
    }, [item]);

    const handleSave = (data: StockTransferItemFormData) => {
        onSave(
            locked
                ? {
                      ...defaultValues,
                      quantity_received: data.quantity_received,
                  }
                : data,
        );
    };

    const form = useForm<
        StockTransferItemFormData,
        unknown,
        StockTransferItemFormData
    >({
        resolver: zodResolver(stockTransferItemSchema) as Resolver<
            StockTransferItemFormData,
            unknown,
            StockTransferItemFormData
        >,
        defaultValues,
    });

    useResetFormOnDefaultValues(form, defaultValues, { enabled: open });

    return (
        <ItemFormDialogShell
            open={open}
            onOpenChange={onOpenChange}
            item={item}
            form={form}
            onSave={handleSave}
            itemDescription="stock transfer item"
        >
            {locked ? (
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div className="space-y-1 text-sm">
                        <div className="font-medium">Product</div>
                        <div>
                            {defaultValues.product_label ||
                                defaultValues.product_id}
                        </div>
                    </div>
                    <div className="space-y-1 text-sm">
                        <div className="font-medium">Unit</div>
                        <div>
                            {defaultValues.unit_label || defaultValues.unit_id}
                        </div>
                    </div>
                    <div className="space-y-1 text-sm">
                        <div className="font-medium">Quantity</div>
                        <div>{defaultValues.quantity}</div>
                    </div>
                    <InputField
                        name="quantity_received"
                        label={t('stockTransfer.qty_received_label')}
                        type="number"
                        min={0}
                        step="any"
                    />
                </div>
            ) : (
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <ItemProductUnitFields
                        form={form}
                        open={open}
                        productInitialId={defaultValues.product_id}
                        productInitialLabel={defaultValues.product_label}
                        unitInitialId={defaultValues.unit_id}
                        unitInitialLabel={defaultValues.unit_label}
                    />
                    <InputField
                        name="quantity"
                        label="Quantity"
                        type="number"
                        min={0}
                        step="any"
                        placeholder="1"
                    />
                    <InputField
                        name="quantity_received"
                        label={t('stockTransfer.qty_received_label')}
                        type="number"
                        min={0}
                        step="any"
                        placeholder={t(
                            'stockTransfer.qty_received_placeholder',
                        )}
                    />
                    <InputField
                        name="unit_cost"
                        label="Unit Cost"
                        type="number"
                        min={0}
                        step="any"
                        placeholder="0"
                    />
                </div>
            )}

            {!locked && <ItemNotesField />}
        </ItemFormDialogShell>
    );
}
