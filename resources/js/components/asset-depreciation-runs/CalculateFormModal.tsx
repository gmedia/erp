import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Form } from '@/components/ui/form';
import { useTranslation } from '@/contexts/i18n-context';
import { type FiscalYear } from '@/types/fiscal-year';
import { type AssetDepreciationCalculationFormData } from '@/utils/schemas';
import { Loader2 } from 'lucide-react';
import { useForm } from 'react-hook-form';

interface CalculateFormValues {
    fiscal_year_id: string;
    period_start?: string;
    period_end?: string;
}

interface CalculateFormModalProps {
    open: boolean;
    onClose: () => void;
    onSubmit: (
        data: AssetDepreciationCalculationFormData,
    ) => Promise<{ success: boolean; errors?: Record<string, string[]> }>;
    loading: boolean;
}

export function CalculateFormModal({
    open,
    onClose,
    onSubmit,
    loading,
}: Readonly<CalculateFormModalProps>) {
    const form = useForm<CalculateFormValues>({
        defaultValues: {
            fiscal_year_id: '',
            period_start: undefined,
            period_end: undefined,
        },
    });

    const { handleSubmit, setError, reset } = form;
    const { t } = useTranslation();
    const fl = (key: string, fallback: string) => {
        const translated = t(`form_fields.asset_depreciation_runs.${key}`);
        return translated === `form_fields.asset_depreciation_runs.${key}`
            ? fallback
            : translated;
    };

    const handleFormSubmit = async (data: CalculateFormValues) => {
        if (!data.period_start) {
            setError('period_start', {
                type: 'required',
                message:
                    'validation.asset_depreciation_calculation.period_start_date_required',
            });

            return;
        }

        if (!data.period_end) {
            setError('period_end', {
                type: 'required',
                message:
                    'validation.asset_depreciation_calculation.period_end_date_required',
            });

            return;
        }

        const payload: AssetDepreciationCalculationFormData = {
            fiscal_year_id: data.fiscal_year_id,
            period_start: data.period_start,
            period_end: data.period_end,
        };

        const result = await onSubmit(payload);

        if (result?.errors) {
            const errors = result.errors;
            Object.keys(errors).forEach((key) => {
                setError(key as keyof CalculateFormValues, {
                    type: 'server',
                    message:
                        errors[key]?.[0] ??
                        fl('calculate_fallback_error', 'An error occurred.'),
                });
            });
        } else if (result) {
            reset();
            onClose();
        }
    };

    return (
        <Dialog open={open} onOpenChange={(val) => !val && onClose()}>
            <DialogContent className="sm:max-w-[425px]">
                <Form {...form}>
                    <form onSubmit={handleSubmit(handleFormSubmit)}>
                        <DialogHeader>
                            <DialogTitle>
                                {fl(
                                    'calculate_title',
                                    'Calculate Depreciation',
                                )}
                            </DialogTitle>
                            <DialogDescription>
                                {fl(
                                    'calculate_description',
                                    'Select the fiscal year and period to calculate asset depreciation.',
                                )}
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-4 py-4">
                            <AsyncSelectField<FiscalYear>
                                name="fiscal_year_id"
                                label={fl('fiscal_year', 'Fiscal Year')}
                                url="/api/fiscal-years"
                                placeholder={fl(
                                    'fiscal_year_placeholder',
                                    'Select Fiscal Year...',
                                )}
                                labelFn={(fy: FiscalYear) => fy.name}
                                valueFn={(fy: FiscalYear) => fy.id.toString()}
                                preferredMetaKey="preferred_fiscal_year_id"
                            />

                            <div className="grid grid-cols-2 gap-4">
                                <DatePickerField
                                    name="period_start"
                                    label={fl('period_start', 'Period Start')}
                                    placeholder={fl(
                                        'period_start_placeholder',
                                        'Pick period start',
                                    )}
                                />
                                <DatePickerField
                                    name="period_end"
                                    label={fl('period_end', 'Period End')}
                                    placeholder={fl(
                                        'period_end_placeholder',
                                        'Pick period end',
                                    )}
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={onClose}
                                disabled={loading}
                            >
                                {fl('cancel', 'Cancel')}
                            </Button>
                            <Button type="submit" disabled={loading}>
                                {loading && (
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                )}
                                {fl('calculate', 'Calculate')}
                            </Button>
                        </DialogFooter>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    );
}
