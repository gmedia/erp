import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Badge } from '@/components/ui/badge';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import {
    formatCurrencyByRegionalSettings,
    formatNumberByRegionalSettings,
} from '@/utils/number-format';
import React from 'react';

import { PurchaseRequest } from '@/types/purchase-request';

interface PurchaseRequestViewModalProps {
    open: boolean;
    onClose: () => void;
    item: PurchaseRequest | null;
}

const formatAmount = (value: string | number | null | undefined): string =>
    formatCurrencyByRegionalSettings(value ?? 0, {
        locale: 'id-ID',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

const formatQuantity = (value: string | number | null | undefined): string =>
    formatNumberByRegionalSettings(value ?? 0, {
        locale: 'id-ID',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });

export const PurchaseRequestViewModal = React.memo(
    ({ item, open, onClose }: PurchaseRequestViewModalProps) => {
        if (!item) return null;

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="Purchase Request Details"
                description="View details and requested items"
                contentClassName="flex max-h-[90vh] max-w-[95vw] flex-col overflow-hidden sm:max-w-7xl"
            >
                <div className="min-h-0 flex-1 overflow-y-auto sm:pr-4">
                    <div className="space-y-6 py-2">
                            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <ViewField
                                    label="PR Number"
                                    value={item.pr_number}
                                />
                                <ViewField
                                    label="Branch"
                                    value={item.branch?.name}
                                />
                                <ViewField
                                    label="Department"
                                    value={item.department?.name}
                                />
                                <ViewField
                                    label="Requested By"
                                    value={item.requester?.name}
                                />
                                <ViewField
                                    label="Request Date"
                                    value={formatDateByRegionalSettings(
                                        item.request_date,
                                    )}
                                />
                                <ViewField
                                    label="Required Date"
                                    value={formatDateByRegionalSettings(
                                        item.required_date,
                                    )}
                                />
                                <ViewField
                                    label="Priority"
                                    value={<Badge>{item.priority}</Badge>}
                                />
                                <ViewField
                                    label="Status"
                                    value={
                                        <Badge variant="outline">
                                            {item.status}
                                        </Badge>
                                    }
                                />
                                <ViewField
                                    label="Estimated Amount"
                                    value={formatAmount(item.estimated_amount)}
                                />
                                <ViewField
                                    label="Notes"
                                    value={item.notes || '-'}
                                />
                            </div>

                            <div className="space-y-2">
                                <h4 className="text-sm font-semibold">Items</h4>
                                <div className="overflow-x-auto rounded-md border">
                                    <table className="min-w-[720px] text-sm">
                                        <thead>
                                            <tr className="border-b">
                                                <th className="p-2 text-left">
                                                    Product
                                                </th>
                                                <th className="p-2 text-left">
                                                    Unit
                                                </th>
                                                <th className="p-2 text-right">
                                                    Quantity
                                                </th>
                                                <th className="p-2 text-right">
                                                    Est. Unit Price
                                                </th>
                                                <th className="p-2 text-right">
                                                    Est. Total
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {(item.items || []).map((it) => (
                                                <tr
                                                    key={it.id}
                                                    className="border-b last:border-b-0"
                                                >
                                                    <td className="p-2">
                                                        {it.product?.name ||
                                                            '-'}
                                                    </td>
                                                    <td className="p-2">
                                                        {it.unit?.name || '-'}
                                                    </td>
                                                    <td className="p-2 text-right">
                                                        {formatQuantity(
                                                            it.quantity,
                                                        )}
                                                    </td>
                                                    <td className="p-2 text-right">
                                                        {formatAmount(
                                                            it.estimated_unit_price,
                                                        )}
                                                    </td>
                                                    <td className="p-2 text-right">
                                                        {formatAmount(
                                                            it.estimated_total,
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                    </div>
                </div>
            </ViewModalShell>
        );
    },
);
