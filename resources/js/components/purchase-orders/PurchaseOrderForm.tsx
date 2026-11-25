'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useMemo } from 'react';
import { useFieldArray, useForm, type Resolver } from 'react-hook-form';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import {
    EntityFormItemActionsCell,
    EntityFormItemEmptyRow,
    EntityFormItemSectionHeader,
} from '@/components/common/EntityFormItemTable';
import { InputField } from '@/components/common/InputField';
import {
    ItemTableHeader,
    type ItemTableColumn,
} from '@/components/common/ItemTableHeader';
import { NotesTextareaField } from '@/components/common/NotesTextareaField';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Table, TableBody, TableCell, TableRow } from '@/components/ui/table';
import { useTranslation } from '@/contexts/i18n-context';
import { useEntityFormItemDialog } from '@/hooks/useEntityFormItemDialog';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import {
    type PurchaseOrder,
    type PurchaseOrderFormData,
} from '@/types/purchase-order';
import { toDateInputValue } from '@/utils/date-format';
import {
    formatItemReference,
    omitItemDisplayLabels,
} from '@/utils/entity-form-item';
import {
    formatCurrencyByRegionalSettings,
    formatNumberByRegionalSettings,
} from '@/utils/number-format';
import { purchaseOrderFormSchema } from '@/utils/schemas';
import { PurchaseOrderItemFormDialog } from './PurchaseOrderItemFormDialog';

interface PurchaseOrderFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    purchaseOrder?: PurchaseOrder | null;
    item?: PurchaseOrder | null;
    entity?: PurchaseOrder | null;
    onSubmit: (data: PurchaseOrderFormData) => void;
    isLoading?: boolean;
}

const PURCHASE_ORDER_ITEM_COLUMNS: readonly ItemTableColumn[] = [
    { key: 'item_product', fallback: 'Product', className: 'w-[220px]' },
    { key: 'item_unit', fallback: 'Unit', className: 'w-[140px]' },
    { key: 'item_qty', fallback: 'Qty', className: 'w-[120px]' },
    { key: 'item_unit_price', fallback: 'Unit Price', className: 'w-[140px]' },
    { key: 'item_discount', fallback: 'Disc %', className: 'w-[130px]' },
    { key: 'item_tax', fallback: 'Tax %', className: 'w-[130px]' },
    { key: 'item_notes', fallback: 'Notes' },
    {
        key: 'item_action',
        fallback: 'Action',
        className: 'w-[120px] text-right',
    },
];

const createEmptyPurchaseOrderItem =
    (): PurchaseOrderFormData['items'][number] => ({
        purchase_request_item_id: '',
        product_id: '',
        product_label: '',
        unit_id: '',
        unit_label: '',
        quantity: 1,
        unit_price: 0,
        discount_percent: 0,
        tax_percent: 0,
        notes: '',
    });

const getPurchaseOrderFormDefaults = (
    purchaseOrder?: PurchaseOrder | null,
): PurchaseOrderFormData => {
    if (!purchaseOrder) {
        return {
            po_number: '',
            supplier_id: '',
            warehouse_id: '',
            order_date: toDateInputValue(new Date()),
            expected_delivery_date: null,
            payment_terms: '',
            currency: 'IDR',
            status: 'draft',
            notes: '',
            shipping_address: '',
            items: [],
        };
    }

    return {
        po_number: purchaseOrder.po_number || '',
        supplier_id: purchaseOrder.supplier?.id
            ? String(purchaseOrder.supplier.id)
            : '',
        warehouse_id: purchaseOrder.warehouse?.id
            ? String(purchaseOrder.warehouse.id)
            : '',
        order_date: purchaseOrder.order_date
            ? toDateInputValue(purchaseOrder.order_date)
            : toDateInputValue(new Date()),
        expected_delivery_date: purchaseOrder.expected_delivery_date
            ? toDateInputValue(purchaseOrder.expected_delivery_date)
            : null,
        payment_terms: purchaseOrder.payment_terms || '',
        currency: purchaseOrder.currency || 'IDR',
        status: purchaseOrder.status,
        notes: purchaseOrder.notes || '',
        shipping_address: purchaseOrder.shipping_address || '',
        items: (purchaseOrder.items || []).length
            ? (purchaseOrder.items || []).map((it) => ({
                  purchase_request_item_id: it.purchase_request_item_id
                      ? String(it.purchase_request_item_id)
                      : '',
                  product_id: it.product?.id ? String(it.product.id) : '',
                  product_label: it.product?.name || '',
                  unit_id: it.unit?.id ? String(it.unit.id) : '',
                  unit_label: it.unit?.name || '',
                  quantity: Number(it.quantity || 0),
                  unit_price: Number(it.unit_price || 0),
                  discount_percent: Number(it.discount_percent || 0),
                  tax_percent: Number(it.tax_percent || 0),
                  notes: it.notes || '',
              }))
            : [],
    };
};

export const PurchaseOrderForm = memo<PurchaseOrderFormProps>(
    function PurchaseOrderForm({
        open,
        onOpenChange,
        purchaseOrder,
        item,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const activePurchaseOrder = purchaseOrder || item || entity;
        // Header dan item terkunci sejak pending_approval/confirmed (sejalan dengan validasi backend).
        const isLocked = Boolean(
            activePurchaseOrder && activePurchaseOrder.status !== 'draft',
        );
        const { t } = useTranslation();
        const fl = (key: string, fallback: string) => {
            const translated = t(`form_fields.purchase_orders.${key}`);
            return translated === `form_fields.purchase_orders.${key}`
                ? fallback
                : translated;
        };
        const defaultValues = useMemo(
            () => getPurchaseOrderFormDefaults(activePurchaseOrder),
            [activePurchaseOrder],
        );

        const form = useForm<
            PurchaseOrderFormData,
            unknown,
            PurchaseOrderFormData
        >({
            resolver: zodResolver(purchaseOrderFormSchema) as Resolver<
                PurchaseOrderFormData,
                unknown,
                PurchaseOrderFormData
            >,
            defaultValues,
        });

        const { fields, append, remove, update } = useFieldArray({
            control: form.control,
            name: 'items',
        });
        const watchedItems = form.watch('items');
        const selectedCurrency = form.watch('currency');
        const {
            isItemDialogOpen,
            item: selectedItem,
            handleCreateNewItem,
            handleEditItem,
            handleItemDialogOpenChange,
            handleSaveItem,
        } = useEntityFormItemDialog({
            items: watchedItems,
            appendItem: append,
            updateItem: update,
        });

        const handleSubmit = (data: PurchaseOrderFormData) => {
            onSubmit({
                ...data,
                items: data.items.map(omitItemDisplayLabels),
            });
        };

        useResetFormOnDefaultValues(form, defaultValues);

        return (
            <EntityForm<PurchaseOrderFormData>
                form={form}
                open={open}
                onOpenChange={onOpenChange}
                title={
                    activePurchaseOrder
                        ? fl('edit_title', 'Edit Purchase Order')
                        : fl('add_title', 'Add New Purchase Order')
                }
                onSubmit={handleSubmit}
                isLoading={isLoading}
                submitDisabled={isLocked}
                className="sm:max-w-[1100px]"
            >
                {isLocked && (
                    <Alert>
                        <AlertDescription>
                            {t('approval.form_locked')}
                        </AlertDescription>
                    </Alert>
                )}
                <fieldset disabled={isLocked} className="contents">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <InputField
                            name="po_number"
                            label={fl('po_number', 'PO Number')}
                            placeholder={fl(
                                'po_number_placeholder',
                                'Auto-generated if empty',
                            )}
                        />

                        <SelectField
                            name="status"
                            label={fl('status', 'Status')}
                            options={[
                                {
                                    value: 'draft',
                                    label: fl('status_draft', 'Draft'),
                                },
                                {
                                    value: 'pending_approval',
                                    label: fl(
                                        'status_pending_approval',
                                        'Pending Approval',
                                    ),
                                },
                                ...(purchaseOrder?.status === 'confirmed'
                                    ? [
                                          {
                                              value: 'confirmed',
                                              label: fl(
                                                  'status_confirmed',
                                                  'Confirmed',
                                              ),
                                          },
                                      ]
                                    : []),
                                ...(purchaseOrder?.status === 'rejected'
                                    ? [
                                          {
                                              value: 'rejected',
                                              label: fl(
                                                  'status_rejected',
                                                  'Rejected',
                                              ),
                                          },
                                      ]
                                    : []),
                                {
                                    value: 'partially_received',
                                    label: fl(
                                        'status_partially_received',
                                        'Partially Received',
                                    ),
                                },
                                {
                                    value: 'fully_received',
                                    label: fl(
                                        'status_fully_received',
                                        'Fully Received',
                                    ),
                                },
                                {
                                    value: 'cancelled',
                                    label: fl('status_cancelled', 'Cancelled'),
                                },
                                {
                                    value: 'closed',
                                    label: fl('status_closed', 'Closed'),
                                },
                            ]}
                            placeholder={fl(
                                'status_placeholder',
                                'Select status',
                            )}
                        />

                        <AsyncSelectField
                            name="supplier_id"
                            label={fl('supplier', 'Supplier')}
                            url="/api/suppliers"
                            placeholder={fl(
                                'supplier_placeholder',
                                'Select supplier',
                            )}
                        />

                        <AsyncSelectField
                            name="warehouse_id"
                            label={fl('warehouse', 'Warehouse')}
                            url="/api/warehouses"
                            placeholder={fl(
                                'warehouse_placeholder',
                                'Select warehouse',
                            )}
                        />

                        <DatePickerField
                            name="order_date"
                            label={fl('order_date', 'Order Date')}
                        />
                        <DatePickerField
                            name="expected_delivery_date"
                            label={fl(
                                'expected_delivery_date',
                                'Expected Delivery Date',
                            )}
                        />

                        <InputField
                            name="payment_terms"
                            label={fl('payment_terms', 'Payment Terms')}
                            placeholder={fl(
                                'payment_terms_placeholder',
                                'Net 30',
                            )}
                        />

                        <div className="md:col-span-2">
                            <TextareaField
                                name="shipping_address"
                                label={fl(
                                    'shipping_address',
                                    'Shipping Address',
                                )}
                                placeholder={fl(
                                    'shipping_address_placeholder',
                                    'Shipping address',
                                )}
                                rows={2}
                            />
                        </div>

                        <NotesTextareaField
                            label={fl('notes', 'Notes')}
                            placeholder={fl('notes_placeholder', 'Notes')}
                        />
                    </div>

                    <div className="mt-2 space-y-3 border-t pt-4">
                        <EntityFormItemSectionHeader
                            onAddItem={handleCreateNewItem}
                        />

                        <div className="rounded-md border">
                            <Table>
                                <ItemTableHeader
                                    columns={PURCHASE_ORDER_ITEM_COLUMNS}
                                    translate={fl}
                                />
                                <TableBody>
                                    {fields.map((field, index) => {
                                        const purchaseOrderItem =
                                            watchedItems?.[index] ??
                                            createEmptyPurchaseOrderItem();

                                        return (
                                            <TableRow key={field.id}>
                                                <TableCell>
                                                    {formatItemReference(
                                                        purchaseOrderItem.product_label,
                                                        purchaseOrderItem.product_id,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {formatItemReference(
                                                        purchaseOrderItem.unit_label,
                                                        purchaseOrderItem.unit_id,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {formatNumberByRegionalSettings(
                                                        purchaseOrderItem.quantity ??
                                                            0,
                                                        {
                                                            locale: 'id-ID',
                                                            minimumFractionDigits: 0,
                                                            maximumFractionDigits: 2,
                                                        },
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {formatCurrencyByRegionalSettings(
                                                        purchaseOrderItem.unit_price ??
                                                            0,
                                                        {
                                                            locale: 'id-ID',
                                                            currency:
                                                                selectedCurrency ||
                                                                undefined,
                                                            minimumFractionDigits: 0,
                                                            maximumFractionDigits: 2,
                                                        },
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {formatNumberByRegionalSettings(
                                                        purchaseOrderItem.discount_percent ??
                                                            0,
                                                        {
                                                            locale: 'id-ID',
                                                            minimumFractionDigits: 0,
                                                            maximumFractionDigits: 2,
                                                        },
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {formatNumberByRegionalSettings(
                                                        purchaseOrderItem.tax_percent ??
                                                            0,
                                                        {
                                                            locale: 'id-ID',
                                                            minimumFractionDigits: 0,
                                                            maximumFractionDigits: 2,
                                                        },
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {purchaseOrderItem.notes ||
                                                        '-'}
                                                </TableCell>
                                                <EntityFormItemActionsCell
                                                    index={index}
                                                    onEdit={handleEditItem}
                                                    onRemove={remove}
                                                />
                                            </TableRow>
                                        );
                                    })}
                                    {fields.length === 0 && (
                                        <EntityFormItemEmptyRow colSpan={8} />
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                </fieldset>

                <PurchaseOrderItemFormDialog
                    open={isItemDialogOpen}
                    onOpenChange={handleItemDialogOpenChange}
                    item={selectedItem}
                    onSave={handleSaveItem}
                />
            </EntityForm>
        );
    },
);
