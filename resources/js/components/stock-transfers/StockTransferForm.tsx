'use client';

import axios from '@/lib/axios';
import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo, useRef } from 'react';
import { Controller, useFieldArray, useForm } from 'react-hook-form';
import { z } from 'zod';

import { AsyncSelect } from '@/components/common/AsyncSelect';
import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { StockTransfer } from '@/types/stock-transfer';
import {
    stockTransferFormSchema,
    type StockTransferFormData,
} from '@/utils/schemas';

interface StockTransferFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    stockTransfer?: StockTransfer | null;
    item?: StockTransfer | null;
    entity?: StockTransfer | null;
    onSubmit: (data: StockTransferFormData) => void;
    isLoading?: boolean;
}

const getStockTransferFormDefaults = (
    stockTransfer?: StockTransfer | null,
): StockTransferFormData => {
    if (!stockTransfer) {
        return {
            transfer_number: '',
            from_warehouse_id: '',
            to_warehouse_id: '',
            transfer_date: new Date(),
            expected_arrival_date: null,
            status: 'draft',
            notes: '',
            requested_by: '',
            items: [
                {
                    product_id: '',
                    unit_id: '',
                    quantity: 1,
                    quantity_received: 0,
                    unit_cost: 0,
                    notes: '',
                },
            ],
        };
    }

    const items = stockTransfer.items ?? [];

    return {
        transfer_number: stockTransfer.transfer_number || '',
        from_warehouse_id: stockTransfer.from_warehouse?.id
            ? String(stockTransfer.from_warehouse.id)
            : '',
        to_warehouse_id: stockTransfer.to_warehouse?.id
            ? String(stockTransfer.to_warehouse.id)
            : '',
        transfer_date: stockTransfer.transfer_date
            ? new Date(stockTransfer.transfer_date)
            : new Date(),
        expected_arrival_date: stockTransfer.expected_arrival_date
            ? new Date(stockTransfer.expected_arrival_date)
            : null,
        status: stockTransfer.status,
        notes: stockTransfer.notes || '',
        requested_by: stockTransfer.requested_by?.id
            ? String(stockTransfer.requested_by.id)
            : '',
        items: items.length
            ? items.map((item) => ({
                  product_id: item.product?.id ? String(item.product.id) : '',
                  unit_id: item.unit?.id ? String(item.unit.id) : '',
                  quantity: Number(item.quantity || 0),
                  quantity_received: Number(item.quantity_received || 0),
                  unit_cost: Number(item.unit_cost || 0),
                  notes: item.notes || '',
              }))
            : [
                  {
                      product_id: '',
                      unit_id: '',
                      quantity: 1,
                      quantity_received: 0,
                      unit_cost: 0,
                      notes: '',
                  },
              ],
    };
};

export const StockTransferForm = memo<StockTransferFormProps>(
    function StockTransferForm({
        open,
        onOpenChange,
        stockTransfer,
        item,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const activeStockTransfer = stockTransfer || item || entity;
        const loadedIdRef = useRef<number | null>(null);

        const defaultValues = useMemo(
            () => getStockTransferFormDefaults(activeStockTransfer),
            [activeStockTransfer],
        );

        type StockTransferFormInput = z.input<typeof stockTransferFormSchema>;

        const form = useForm<
            StockTransferFormInput,
            unknown,
            StockTransferFormData
        >({
            resolver: zodResolver(stockTransferFormSchema),
            defaultValues,
        });

        const { fields, append, remove } = useFieldArray({
            control: form.control,
            name: 'items',
        });

        useEffect(() => {
            form.reset(defaultValues);
        }, [form, defaultValues]);

        useEffect(() => {
            const loadDetail = async () => {
                if (!open) return;
                if (!activeStockTransfer?.id) return;
                if (loadedIdRef.current === activeStockTransfer.id) return;
                if (
                    activeStockTransfer.items &&
                    activeStockTransfer.items.length > 0
                ) {
                    loadedIdRef.current = activeStockTransfer.id;
                    return;
                }

                try {
                    const response = await axios.get(
                        `/api/stock-transfers/${activeStockTransfer.id}`,
                    );
                    const data = response.data?.data ?? response.data;
                    form.reset(getStockTransferFormDefaults(data));
                    loadedIdRef.current = activeStockTransfer.id;
                } catch {
                    loadedIdRef.current = activeStockTransfer.id;
                }
            };

            loadDetail();
        }, [open, activeStockTransfer?.id, activeStockTransfer?.items, form]);

        return (
            <EntityForm<StockTransferFormInput, StockTransferFormData>
                form={form}
                open={open}
                onOpenChange={onOpenChange}
                title={
                    activeStockTransfer
                        ? 'Edit Stock Transfer'
                        : 'Add New Stock Transfer'
                }
                onSubmit={onSubmit}
                isLoading={isLoading}
                className="sm:max-w-[900px]"
            >
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <InputField
                        name="transfer_number"
                        label="Transfer Number"
                        placeholder="Auto-generated if empty"
                    />
                    <SelectField
                        name="status"
                        label="Status"
                        options={[
                            { value: 'draft', label: 'Draft' },
                            {
                                value: 'pending_approval',
                                label: 'Pending Approval',
                            },
                            { value: 'approved', label: 'Approved' },
                            { value: 'in_transit', label: 'In Transit' },
                            { value: 'received', label: 'Received' },
                            { value: 'cancelled', label: 'Cancelled' },
                        ]}
                        placeholder="Select status"
                    />
                    <div className="grid grid-cols-1 gap-4 md:col-span-2 md:grid-cols-2">
                        <Controller
                            control={form.control}
                            name="from_warehouse_id"
                            render={({ field }) => (
                                <div className="space-y-2">
                                    <div className="text-sm leading-none font-medium">
                                        From Warehouse
                                    </div>
                                    <AsyncSelect
                                        value={
                                            field.value
                                                ? String(field.value)
                                                : undefined
                                        }
                                        onValueChange={field.onChange}
                                        url="/api/warehouses"
                                        placeholder="Select warehouse"
                                        label="From Warehouse"
                                    />
                                </div>
                            )}
                        />
                        <Controller
                            control={form.control}
                            name="to_warehouse_id"
                            render={({ field }) => (
                                <div className="space-y-2">
                                    <div className="text-sm leading-none font-medium">
                                        To Warehouse
                                    </div>
                                    <AsyncSelect
                                        value={
                                            field.value
                                                ? String(field.value)
                                                : undefined
                                        }
                                        onValueChange={field.onChange}
                                        url="/api/warehouses"
                                        placeholder="Select warehouse"
                                        label="To Warehouse"
                                    />
                                </div>
                            )}
                        />
                    </div>
                    <DatePickerField
                        name="transfer_date"
                        label="Transfer Date"
                    />
                    <DatePickerField
                        name="expected_arrival_date"
                        label="Expected Arrival Date"
                    />
                    <Controller
                        control={form.control}
                        name="requested_by"
                        render={({ field }) => (
                            <div className="space-y-2">
                                <div className="text-sm leading-none font-medium">
                                    Requested By (Employee)
                                </div>
                                <AsyncSelect
                                    value={
                                        field.value
                                            ? String(field.value)
                                            : undefined
                                    }
                                    onValueChange={field.onChange}
                                    url="/api/employees"
                                    placeholder="Select employee"
                                    label="Requested By"
                                />
                            </div>
                        )}
                    />
                    <div className="md:col-span-2">
                        <TextareaField
                            name="notes"
                            label="Notes"
                            placeholder="Additional notes..."
                        />
                    </div>
                </div>

                <div className="mt-2 space-y-3 border-t pt-4">
                    <div className="flex items-center justify-between">
                        <div className="text-sm font-semibold">Items</div>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                                append({
                                    product_id: '',
                                    unit_id: '',
                                    quantity: 1,
                                    quantity_received: 0,
                                    unit_cost: 0,
                                    notes: '',
                                })
                            }
                        >
                            Add Item
                        </Button>
                    </div>

                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[260px]">
                                        Product
                                    </TableHead>
                                    <TableHead className="w-[160px]">
                                        Unit
                                    </TableHead>
                                    <TableHead className="w-[120px]">
                                        Qty
                                    </TableHead>
                                    <TableHead className="w-[140px]">
                                        Qty Received
                                    </TableHead>
                                    <TableHead className="w-[140px]">
                                        Unit Cost
                                    </TableHead>
                                    <TableHead>Notes</TableHead>
                                    <TableHead className="w-[80px]"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {fields.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            No items.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    fields.map((field, index) => (
                                        <TableRow key={field.id}>
                                            <TableCell>
                                                <Controller
                                                    control={form.control}
                                                    name={`items.${index}.product_id`}
                                                    render={({
                                                        field: productField,
                                                    }) => (
                                                        <AsyncSelect
                                                            value={
                                                                productField.value
                                                                    ? String(
                                                                          productField.value,
                                                                      )
                                                                    : undefined
                                                            }
                                                            onValueChange={
                                                                productField.onChange
                                                            }
                                                            url="/api/products"
                                                            placeholder="Select product"
                                                            label="Product"
                                                        />
                                                    )}
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Controller
                                                    control={form.control}
                                                    name={`items.${index}.unit_id`}
                                                    render={({
                                                        field: unitField,
                                                    }) => (
                                                        <AsyncSelect
                                                            value={
                                                                unitField.value
                                                                    ? String(
                                                                          unitField.value,
                                                                      )
                                                                    : undefined
                                                            }
                                                            onValueChange={
                                                                unitField.onChange
                                                            }
                                                            url="/api/units"
                                                            placeholder="Select unit"
                                                            label="Unit"
                                                        />
                                                    )}
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Controller
                                                    control={form.control}
                                                    name={`items.${index}.quantity`}
                                                    render={({
                                                        field: qtyField,
                                                    }) => (
                                                        <InputField
                                                            name={qtyField.name}
                                                            label=""
                                                            type="number"
                                                            placeholder="0"
                                                            className="space-y-0"
                                                        />
                                                    )}
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Controller
                                                    control={form.control}
                                                    name={`items.${index}.quantity_received`}
                                                    render={({
                                                        field: qtyReceivedField,
                                                    }) => (
                                                        <InputField
                                                            name={
                                                                qtyReceivedField.name
                                                            }
                                                            label=""
                                                            type="number"
                                                            placeholder="0"
                                                            className="space-y-0"
                                                        />
                                                    )}
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Controller
                                                    control={form.control}
                                                    name={`items.${index}.unit_cost`}
                                                    render={({
                                                        field: costField,
                                                    }) => (
                                                        <InputField
                                                            name={
                                                                costField.name
                                                            }
                                                            label=""
                                                            type="number"
                                                            placeholder="0"
                                                            className="space-y-0"
                                                        />
                                                    )}
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Controller
                                                    control={form.control}
                                                    name={`items.${index}.notes`}
                                                    render={({
                                                        field: notesField,
                                                    }) => (
                                                        <InputField
                                                            name={
                                                                notesField.name
                                                            }
                                                            label=""
                                                            placeholder="Notes"
                                                            className="space-y-0"
                                                        />
                                                    )}
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    onClick={() =>
                                                        remove(index)
                                                    }
                                                    disabled={
                                                        fields.length === 1
                                                    }
                                                >
                                                    Remove
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </EntityForm>
        );
    },
);
