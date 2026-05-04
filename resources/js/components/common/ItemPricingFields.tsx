'use client';

import { InputField } from '@/components/common/InputField';

export function ItemPricingFields() {
    return (
        <>
            <InputField
                name="quantity"
                label="Quantity"
                type="number"
                min={0}
                step="any"
                placeholder="1"
            />
            <InputField
                name="unit_price"
                label="Unit Price"
                type="number"
                min={0}
                step="any"
                placeholder="0"
            />
            <InputField
                name="discount_percent"
                label="Discount Percent"
                type="number"
                min={0}
                max={100}
                step="any"
                placeholder="0"
            />
            <InputField
                name="tax_percent"
                label="Tax Percent"
                type="number"
                min={0}
                max={100}
                step="any"
                placeholder="0"
            />
        </>
    );
}
