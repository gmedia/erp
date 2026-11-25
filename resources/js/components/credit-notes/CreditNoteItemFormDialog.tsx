'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { useMemo } from 'react';
import { type Resolver, useForm } from 'react-hook-form';
import { z } from 'zod';

import { InputField } from '@/components/common/InputField';
import {
    ItemAccountSelectField,
    ItemFormDialogShell,
    ItemNotesField,
    ItemProductUnitFields,
} from '@/components/common/ItemFormDialog';
import { ItemPricingFields } from '@/components/common/ItemPricingFields';
import { useTranslation } from '@/contexts/i18n-context';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import { type CreditNoteFormData } from '@/types/credit-note';

const creditNoteItemSchema = z.object({
    product_id: z.string().optional(),
    product_label: z.string().optional(),
    account_id: z.string().min(1, {
        message: 'validation.credit_note.account_required',
    }),
    account_label: z.string().optional(),
    description: z.string().min(1, {
        message: 'validation.credit_note.description_required',
    }),
    quantity: z.coerce.number().gt(0, {
        message: 'validation.credit_note.quantity_gt_zero',
    }),
    unit_price: z.coerce.number().min(0, {
        message: 'validation.credit_note.unit_price_min_zero',
    }),
    tax_percent: z.coerce.number().min(0).max(100).optional().default(0),
    notes: z.string().optional(),
});

type CreditNoteItemFormData = CreditNoteFormData['items'][number];

interface CreditNoteItemFormDialogProps {
    readonly open: boolean;
    readonly onOpenChange: (open: boolean) => void;
    readonly item: CreditNoteItemFormData | null;
    readonly onSave: (data: CreditNoteItemFormData) => void;
}

export function CreditNoteItemFormDialog({
    open,
    onOpenChange,
    item,
    onSave,
}: CreditNoteItemFormDialogProps) {
    const { t } = useTranslation();
    const fl = (key: string, fallback: string) => {
        const translated = t(`form_fields.credit_notes.${key}`);
        return translated === `form_fields.credit_notes.${key}`
            ? fallback
            : translated;
    };
    const defaultValues = useMemo<CreditNoteItemFormData>(() => {
        if (!item) {
            return {
                product_id: '',
                product_label: '',
                account_id: '',
                account_label: '',
                description: '',
                quantity: 1,
                unit_price: 0,
                tax_percent: 0,
                notes: '',
            };
        }

        return {
            product_id: item.product_id || '',
            product_label: item.product_label || '',
            account_id: item.account_id || '',
            account_label: item.account_label || '',
            description: item.description || '',
            quantity: Number(item.quantity || 0),
            unit_price: Number(item.unit_price || 0),
            tax_percent: Number(item.tax_percent || 0),
            notes: item.notes || '',
        };
    }, [item]);

    const form = useForm<
        CreditNoteItemFormData,
        unknown,
        CreditNoteItemFormData
    >({
        resolver: zodResolver(creditNoteItemSchema) as Resolver<
            CreditNoteItemFormData,
            unknown,
            CreditNoteItemFormData
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
            onSave={onSave}
            itemDescription="credit note item"
        >
            <ItemProductUnitFields
                form={form}
                open={open}
                productInitialId={defaultValues.product_id}
                productInitialLabel={defaultValues.product_label}
            />
            <div className="grid grid-cols-1 gap-4">
                <ItemAccountSelectField
                    form={form}
                    open={open}
                    name="account_id"
                    labelName="account_label"
                    initialId={defaultValues.account_id}
                    initialLabel={defaultValues.account_label}
                />
                <InputField
                    name="description"
                    label={fl('item_description', 'Description')}
                    placeholder={fl(
                        'item_description_placeholder',
                        'Enter description',
                    )}
                />
            </div>
            <ItemPricingFields includeDiscount={false} />
            <ItemNotesField />
        </ItemFormDialogShell>
    );
}
