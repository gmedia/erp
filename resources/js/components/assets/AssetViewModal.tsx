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
        } catch {
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
            <DialogContent className="max-h-[90vh] max-w-2xl overflow-y-auto">
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

                <div className="grid grid-cols-1 gap-6 py-4 md:grid-cols-2">
                    {/* General Information */}
                    <div className="space-y-4">
                        <h3 className="text-sm font-semibold tracking-wider text-muted-foreground uppercase">
                            General Information
                        </h3>
                        <div className="grid grid-cols-1 gap-2">
                            <div className="flex justify-between border-b border-muted py-1">
                                <span className="text-muted-foreground">
                                    Category:
                                </span>
                                <span className="text-right font-medium">
                                    {item.category?.name || 'N/A'}
                                </span>
                            </div>
                            <div className="flex justify-between border-b border-muted py-1">
                                <span className="text-muted-foreground">
                                    Model:
                                </span>
                                <span className="text-right font-medium">
                                    {item.model?.model_name || 'N/A'}
                                </span>
                            </div>
                            <div className="flex justify-between border-b border-muted py-1">
                                <span className="text-muted-foreground">
                                    Serial Number:
                                </span>
                                <span className="text-right font-medium">
                                    {item.serial_number || 'N/A'}
                                </span>
                            </div>
                            <div className="flex justify-between border-b border-muted py-1">
                                <span className="text-muted-foreground">
                                    Barcode:
                                </span>
                                <span className="text-right font-medium">
                                    {item.barcode || 'N/A'}
                                </span>
                            </div>
                            <div className="flex justify-between border-b border-muted py-1">
                                <span className="text-muted-foreground">
                                    Status:
                                </span>
                                <Badge variant={getStatusVariant(item.status)}>
                                    {item.status.charAt(0).toUpperCase() +
                                        item.status.slice(1)}
                                </Badge>
                            </div>
                            <div className="flex justify-between border-b border-muted py-1">
                                <span className="text-muted-foreground">
                                    Condition:
                                </span>
                                <Badge
                                    variant={getConditionVariant(
                                        item.condition,
                                    )}
                                >
                                    {item.condition
                                        ? item.condition
                                              .replace('_', ' ')
                                              .charAt(0)
                                              .toUpperCase() +
                                          item.condition
                                              .replace('_', ' ')
                                              .slice(1)
                                        : 'N/A'}
                                </Badge>
                            </div>
                        </div>
                    </div>

                    {/* Location & Assignment */}
                    <div className="space-y-4">
                        <h3 className="text-sm font-semibold tracking-wider text-muted-foreground uppercase">
                            Location & Assignment
                        </h3>
                        <div className="grid grid-cols-1 gap-2">
                            <div className="flex justify-between border-b border-muted py-1">
                                <span className="text-muted-foreground">
                                    Branch:
                                </span>
                                <span className="text-right font-medium">
                                    {item.branch?.name || 'N/A'}
                                </span>
                            </div>
                            <div className="flex justify-between border-b border-muted py-1">
                                <span className="text-muted-foreground">
                                    Location:
                                </span>
                                <span className="text-right font-medium">
                                    {item.location?.name || 'N/A'}
                                </span>
                            </div>
                            <div className="flex justify-between border-b border-muted py-1">
                                <span className="text-muted-foreground">
                                    Department:
                                </span>
                                <span className="text-right font-medium">
                                    {item.department?.name || 'N/A'}
                                </span>
                            </div>
                            <div className="flex justify-between border-b border-muted py-1">
                                <span className="text-muted-foreground">
                                    Assigned To:
                                </span>
                                <span className="text-right font-medium">
                                    {item.employee?.name || 'Unassigned'}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Purchase Information */}
                    <div className="space-y-4">
                        <h3 className="text-sm font-semibold tracking-wider text-muted-foreground uppercase">
                            Purchase Information
                        </h3>
                        <div className="grid grid-cols-1 gap-2">
                            <div className="flex justify-between border-b border-muted py-1">
                                <span className="text-muted-foreground">
                                    Supplier:
                                </span>
                                <span className="text-right font-medium">
                                    {item.supplier?.name || 'N/A'}
                                </span>
                            </div>
                            <div className="flex justify-between border-b border-muted py-1">
                                <span className="text-muted-foreground">
                                    Purchase Date:
                                </span>
                                <span className="text-right font-medium">
                                    {formatDate(item.purchase_date)}
                                </span>
                            </div>
                            <div className="flex justify-between border-b border-muted py-1">
                                <span className="text-muted-foreground">
                                    Purchase Cost:
                                </span>
                                <span className="text-right font-medium">
                                    {formatCurrency(item.purchase_cost)}
                                </span>
                            </div>
                            <div className="flex justify-between border-b border-muted py-1">
                                <span className="text-muted-foreground">
                                    Warranty Until:
                                </span>
                                <span className="text-right font-medium">
                                    {formatDate(item.warranty_end_date)}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Financial/Depreciation */}
                    <div className="space-y-4">
                        <h3 className="text-sm font-semibold tracking-wider text-muted-foreground uppercase">
                            Financial & Depreciation
                        </h3>
                        <div className="grid grid-cols-1 gap-2">
                            <div className="flex justify-between border-b border-muted py-1">
                                <span className="text-muted-foreground">
                                    Method:
                                </span>
                                <span className="text-right font-medium">
                                    {item.depreciation_method
                                        ? item.depreciation_method
                                              .replace('_', ' ')
                                              .charAt(0)
                                              .toUpperCase() +
                                          item.depreciation_method
                                              .replace('_', ' ')
                                              .slice(1)
                                        : 'N/A'}
                                </span>
                            </div>
                            <div className="flex justify-between border-b border-muted py-1">
                                <span className="text-muted-foreground">
                                    Useful Life:
                                </span>
                                <span className="text-right font-medium">
                                    {item.useful_life_months
                                        ? `${item.useful_life_months} months`
                                        : 'N/A'}
                                </span>
                            </div>
                            <div className="flex justify-between border-b border-muted py-1">
                                <span className="text-muted-foreground">
                                    Salvage Value:
                                </span>
                                <span className="text-right font-medium">
                                    {formatCurrency(item.salvage_value)}
                                </span>
                            </div>
                            <div className="flex justify-between border-b border-muted py-1">
                                <span className="text-muted-foreground">
                                    Book Value:
                                </span>
                                <span className="text-bold text-right font-medium text-primary">
                                    {formatCurrency(item.book_value)}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {item.notes && (
                    <div className="mt-2 space-y-2">
                        <h3 className="text-sm font-semibold tracking-wider text-muted-foreground uppercase">
                            Notes
                        </h3>
                        <p className="rounded-md bg-muted p-3 text-sm whitespace-pre-wrap">
                            {item.notes}
                        </p>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
