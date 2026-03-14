import AsyncSelectField from '@/components/common/AsyncSelectField';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type FiscalYear } from '@/types/fiscal-year';
import { type AssetDepreciationCalculationFormData } from '@/utils/schemas';
import { Loader2 } from 'lucide-react';
import { useForm } from 'react-hook-form';

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
    const form = useForm<AssetDepreciationCalculationFormData>({
        defaultValues: {
            fiscal_year_id: '',
            period_start: '',
            period_end: '',
        },
    });

    const {
        register,
        handleSubmit,
        formState: { errors },
        setError,
        reset,
    } = form;

    const handleFormSubmit = async (
        data: AssetDepreciationCalculationFormData,
    ) => {
        const result = await onSubmit(data);

        if (result && result.errors) {
            const errors = result.errors;
            Object.keys(errors).forEach((key) => {
                setError(key as keyof AssetDepreciationCalculationFormData, {
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
                                <div className="space-y-2">
                                    <Label
                                        htmlFor="period_start"
                                        className={
                                            errors.period_start
                                                ? 'text-destructive'
                                                : ''
                                        }
                                    >
                                        Period Start
                                    </Label>
                                    <Input
                                        id="period_start"
                                        type="date"
                                        {...register('period_start', {
                                            required: 'Start date is required',
                                        })}
                                        className={
                                            errors.period_start
                                                ? 'border-destructive'
                                                : ''
                                        }
                                    />
                                    {errors.period_start && (
                                        <p className="text-sm text-destructive">
                                            {errors.period_start.message}
                                        </p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label
                                        htmlFor="period_end"
                                        className={
                                            errors.period_end
                                                ? 'text-destructive'
                                                : ''
                                        }
                                    >
                                        Period End
                                    </Label>
                                    <Input
                                        id="period_end"
                                        type="date"
                                        {...register('period_end', {
                                            required: 'End date is required',
                                        })}
                                        className={
                                            errors.period_end
                                                ? 'border-destructive'
                                                : ''
                                        }
                                    />
                                    {errors.period_end && (
                                        <p className="text-sm text-destructive">
                                            {errors.period_end.message}
                                        </p>
                                    )}
                                </div>
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
