import AsyncSelectField from '@/components/common/AsyncSelectField';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { StocktakeItem } from '@/hooks/asset-stocktakes/useStocktakeItems';
import { Loader2, Check } from 'lucide-react';
import { useEffect } from 'react';
import { Controller, FormProvider, useFieldArray, useForm } from 'react-hook-form';

interface FormValues {
    items: StocktakeItem[];
}

interface StocktakeItemManagerProps {
    stocktakeBranchId: number;
    items: StocktakeItem[];
    onSave: (data: { items: StocktakeItem[] }) => Promise<void>;
    loading: boolean;
}

export function StocktakeItemManager({
    stocktakeBranchId,
    items,
    onSave,
    loading,
}: StocktakeItemManagerProps) {
    const form = useForm<FormValues>({
        defaultValues: { items: [] },
    });

    const { control, handleSubmit, reset } = form;

    const { fields } = useFieldArray({
        control,
        name: 'items',
    });

    useEffect(() => {
        reset({ items });
    }, [items, reset]);

    const onSubmit = (data: FormValues) => {
        // Prepare data for submission, e.g. set found_branch_id
        const submitData = {
            items: data.items.map((item) => {
                if (item.result === 'moved') {
                    return {
                        ...item,
                        found_branch_id: stocktakeBranchId, 
                    };
                }
                return {
                    ...item,
                    found_branch_id: null,
                    found_location_id: null,
                };
            }),
        };
        onSave(submitData);
    };

    return (
        <FormProvider {...(form as any)}>
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Asset Code</TableHead>
                                <TableHead>Asset Name</TableHead>
                                <TableHead>Result</TableHead>
                                <TableHead>Location (If Moved)</TableHead>
                                <TableHead>Notes</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {fields.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="text-center text-muted-foreground py-8"
                                    >
                                        No assets expected for this stocktake.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                fields.map((field, index) => (
                                    <TableRow key={field.id}>
                                        <TableCell>
                                            <span className="font-semibold text-sm">
                                                {field.asset?.asset_code}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <span className="text-sm">
                                                {field.asset?.name}
                                            </span>
                                        </TableCell>
                                        <TableCell className="w-[180px]">
                                            <Controller
                                                control={control}
                                                name={`items.${index}.result`}
                                                render={({ field: selectField }) => (
                                                    <Select
                                                        onValueChange={selectField.onChange}
                                                        value={selectField.value}
                                                        disabled={loading}
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Select..." />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="found">Found</SelectItem>
                                                            <SelectItem value="missing">Missing</SelectItem>
                                                            <SelectItem value="damaged">Damaged</SelectItem>
                                                            <SelectItem value="moved">Moved</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                )}
                                            />
                                        </TableCell>
                                        <TableCell className="w-[300px]">
                                            <Controller
                                                control={control}
                                                name={`items.${index}.result`}
                                                render={({ field: resultField }) => {
                                                    if (resultField.value === 'moved') {
                                                        return (
                                                            <AsyncSelectField
                                                                name={`items.${index}.found_location_id`}
                                                                placeholder="Search location..."
                                                                url={`/api/asset-locations?branch_id=${stocktakeBranchId}`}
                                                                labelFn={(item) => item.name}
                                                                valueFn={(item) => String(item.id)}
                                                            />
                                                        );
                                                    }
                                                    return <span className="text-muted-foreground text-sm">-</span>;
                                                }}
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <Controller
                                                control={control}
                                                name={`items.${index}.notes`}
                                                render={({ field: inputField }) => (
                                                    <Input
                                                        {...inputField}
                                                        value={inputField.value || ''}
                                                        placeholder="Notes (optional)"
                                                        disabled={loading}
                                                    />
                                                )}
                                            />
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
                
                <div className="flex justify-end">
                    <Button type="submit" disabled={loading || fields.length === 0}>
                        {loading ? (
                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        ) : (
                            <Check className="mr-2 h-4 w-4" />
                        )}
                        Save Stocktake Items
                    </Button>
                </div>
            </form>
        </FormProvider>
    );
}
