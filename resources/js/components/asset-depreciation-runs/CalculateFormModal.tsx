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
import { type FiscalYear } from '@/types/fiscal-year';
import { type AssetDepreciationCalculationFormData } from '@/utils/schemas';
import { format } from 'date-fns';
import { Loader2 } from 'lucide-react';
import { useForm } from 'react-hook-form';

interface CalculateFormValues {
    fiscal_year_id: string;
    period_start?: Date;
    period_end?: Date;
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

    const handleFormSubmit = async (data: CalculateFormValues) => {
        if (!data.period_start) {
            setError('period_start', {
                type: 'required',
                message: 'Start date is required',
            });

            return;
        }

        if (!data.period_end) {
            setError('period_end', {
                type: 'required',
                message: 'End date is required',
            });

            return;
        }

        const payload: AssetDepreciationCalculationFormData = {
            fiscal_year_id: data.fiscal_year_id,
            period_start: format(data.period_start, 'yyyy-MM-dd'),
            period_end: format(data.period_end, 'yyyy-MM-dd'),
        };

        const result = await onSubmit(payload);

        if (result?.errors) {
            const errors = result.errors;
            Object.keys(errors).forEach((key) => {
                setError(key as keyof CalculateFormValues, {
                    type: 'server',
                    message: errors[key][0],
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
                            <DialogTitle>Calculate Depreciation</DialogTitle>
                            <DialogDescription>
                                Select the fiscal year and period to calculate
                                asset depreciation.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-4 py-4">
                            <AsyncSelectField<FiscalYear>
                                name="fiscal_year_id"
                                label="Fiscal Year"
                                url="/api/fiscal-years"
                                placeholder="Select Fiscal Year..."
                                labelFn={(fy: FiscalYear) => fy.name}
                                valueFn={(fy: FiscalYear) => fy.id.toString()}
                            />

                            <div className="grid grid-cols-2 gap-4">
                                <DatePickerField
                                    name="period_start"
                                    label="Period Start"
                                    placeholder="Pick period start"
                                />
                                <DatePickerField
                                    name="period_end"
                                    label="Period End"
                                    placeholder="Pick period end"
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
                                Cancel
                            </Button>
                            <Button type="submit" disabled={loading}>
                                {loading && (
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                )}
                                Calculate
                            </Button>
                        </DialogFooter>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    );
}
