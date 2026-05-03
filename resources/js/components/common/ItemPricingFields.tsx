import { InputField } from '@/components/common/InputField';

interface ItemPricingFieldsProps {
    includeDiscount?: boolean;
}

export function ItemPricingFields({ includeDiscount = true }: ItemPricingFieldsProps) {
    return (
        <>
            <div className={includeDiscount ? 'grid grid-cols-3 gap-4' : 'grid grid-cols-2 gap-4'}>
                <InputField
                    name="quantity"
                    label="Quantity"
                    type="number"
                    min={0}
                    step={0.01}
                />
                <InputField
                    name="unit_price"
                    label="Unit Price"
                    type="number"
                    min={0}
                    step={0.01}
                />
                {includeDiscount && (
                    <InputField
                        name="discount_percent"
                        label="Discount %"
                        type="number"
                        min={0}
                        max={100}
                        step={0.01}
                    />
                )}
            </div>
            <div className="grid grid-cols-1 gap-4">
                <InputField
                    name="tax_percent"
                    label="Tax %"
                    type="number"
                    min={0}
                    max={100}
                    step={0.01}
                />
            </div>
        </>
    );
}
