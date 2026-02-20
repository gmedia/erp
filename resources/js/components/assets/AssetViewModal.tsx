'use client';

import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { type Asset } from '@/types/asset';
import { format } from 'date-fns';

interface AssetViewModalProps {
    open: boolean;
    onClose: () => void;
    item: Asset | null;
}

export function AssetViewModal({ open, onClose, item }: AssetViewModalProps) {
    if (!item) return null;

    const formatDate = (dateString: string | null) => {
        if (!dateString) return 'N/A';
        try {
            return format(new Date(dateString), 'PPP');
        } catch (e) {
            return dateString;
        }
    };

    const formatCurrency = (value: string | number) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: item.currency || 'USD',
        }).format(Number(value));
    };

    const getStatusVariant = (status: Asset['status']) => {
        switch (status) {
            case 'active':
                return 'default';
            case 'maintenance':
                return 'outline';
            case 'disposed':
                return 'destructive';
            case 'lost':
                return 'destructive';
            default:
                return 'secondary';
        }
    };

    const getConditionVariant = (condition: Asset['condition']) => {
        switch (condition) {
            case 'good':
                return 'default';
            case 'needs_repair':
                return 'outline';
            case 'damaged':
                return 'destructive';
            default:
                return 'secondary';
        }
    };

    return (
        <Dialog open={open} onOpenChange={onClose}>
            <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <span>{item.name}</span>
                        <Badge variant="outline" className="ml-2">
                            {item.asset_code}
                        </Badge>
                    </DialogTitle>
                    <DialogDescription>
                        Detailed information about this asset.
                    </DialogDescription>
                </DialogHeader>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 py-4">
                    {/* General Information */}
                    <div className="space-y-4">
                        <h3 className="font-semibold text-sm text-muted-foreground uppercase tracking-wider">
                            General Information
                        </h3>
                        <div className="grid grid-cols-1 gap-2">
                            <div className="flex justify-between py-1 border-b border-muted">
                                <span className="text-muted-foreground">Category:</span>
                                <span className="font-medium text-right">{item.category?.name || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-muted">
                                <span className="text-muted-foreground">Model:</span>
                                <span className="font-medium text-right">{item.model?.model_name || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-muted">
                                <span className="text-muted-foreground">Serial Number:</span>
                                <span className="font-medium text-right">{item.serial_number || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-muted">
                                <span className="text-muted-foreground">Barcode:</span>
                                <span className="font-medium text-right">{item.barcode || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-muted">
                                <span className="text-muted-foreground">Status:</span>
                                <Badge variant={getStatusVariant(item.status)}>
                                    {item.status.charAt(0).toUpperCase() + item.status.slice(1)}
                                </Badge>
                            </div>
                            <div className="flex justify-between py-1 border-b border-muted">
                                <span className="text-muted-foreground">Condition:</span>
                                <Badge variant={getConditionVariant(item.condition)}>
                                    {item.condition ? item.condition.replace('_', ' ').charAt(0).toUpperCase() + item.condition.replace('_', ' ').slice(1) : 'N/A'}
                                </Badge>
                            </div>
                        </div>
                    </div>

                    {/* Location & Assignment */}
                    <div className="space-y-4">
                        <h3 className="font-semibold text-sm text-muted-foreground uppercase tracking-wider">
                            Location & Assignment
                        </h3>
                        <div className="grid grid-cols-1 gap-2">
                            <div className="flex justify-between py-1 border-b border-muted">
                                <span className="text-muted-foreground">Branch:</span>
                                <span className="font-medium text-right">{item.branch?.name || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-muted">
                                <span className="text-muted-foreground">Location:</span>
                                <span className="font-medium text-right">{item.location?.name || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-muted">
                                <span className="text-muted-foreground">Department:</span>
                                <span className="font-medium text-right">{item.department?.name || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-muted">
                                <span className="text-muted-foreground">Assigned To:</span>
                                <span className="font-medium text-right">{item.employee?.name || 'Unassigned'}</span>
                            </div>
                        </div>
                    </div>

                    {/* Purchase Information */}
                    <div className="space-y-4">
                        <h3 className="font-semibold text-sm text-muted-foreground uppercase tracking-wider">
                            Purchase Information
                        </h3>
                        <div className="grid grid-cols-1 gap-2">
                            <div className="flex justify-between py-1 border-b border-muted">
                                <span className="text-muted-foreground">Supplier:</span>
                                <span className="font-medium text-right">{item.supplier?.name || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-muted">
                                <span className="text-muted-foreground">Purchase Date:</span>
                                <span className="font-medium text-right">{formatDate(item.purchase_date)}</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-muted">
                                <span className="text-muted-foreground">Purchase Cost:</span>
                                <span className="font-medium text-right">{formatCurrency(item.purchase_cost)}</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-muted">
                                <span className="text-muted-foreground">Warranty Until:</span>
                                <span className="font-medium text-right">{formatDate(item.warranty_end_date)}</span>
                            </div>
                        </div>
                    </div>

                    {/* Financial/Depreciation */}
                    <div className="space-y-4">
                        <h3 className="font-semibold text-sm text-muted-foreground uppercase tracking-wider">
                            Financial & Depreciation
                        </h3>
                        <div className="grid grid-cols-1 gap-2">
                            <div className="flex justify-between py-1 border-b border-muted">
                                <span className="text-muted-foreground">Method:</span>
                                <span className="font-medium text-right">
                                    {item.depreciation_method ? item.depreciation_method.replace('_', ' ').charAt(0).toUpperCase() + item.depreciation_method.replace('_', ' ').slice(1) : 'N/A'}
                                </span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-muted">
                                <span className="text-muted-foreground">Useful Life:</span>
                                <span className="font-medium text-right">
                                    {item.useful_life_months ? `${item.useful_life_months} months` : 'N/A'}
                                </span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-muted">
                                <span className="text-muted-foreground">Salvage Value:</span>
                                <span className="font-medium text-right">{formatCurrency(item.salvage_value)}</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-muted">
                                <span className="text-muted-foreground">Book Value:</span>
                                <span className="font-medium text-bold text-primary text-right">{formatCurrency(item.book_value)}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {item.notes && (
                    <div className="space-y-2 mt-2">
                        <h3 className="font-semibold text-sm text-muted-foreground uppercase tracking-wider">
                            Notes
                        </h3>
                        <p className="text-sm p-3 bg-muted rounded-md whitespace-pre-wrap">
                            {item.notes}
                        </p>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
