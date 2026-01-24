import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { format } from 'date-fns';
import React from 'react';

interface Supplier {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    address: string;
    branch?: {
        id: number;
        name: string;
    } | null;
    category: string;
    status: string;
    created_at: string;
    updated_at: string;
}

interface SupplierViewModalProps {
    open: boolean;
    onClose: () => void;
    item: Supplier | null;
}

const ViewField = ({ label, value }: { label: string; value: React.ReactNode }) => (
    <div className="space-y-1">
        <h4 className="text-sm font-medium text-muted-foreground">{label}</h4>
        <div className="text-sm font-medium">{value || '-'}</div>
    </div>
);

export const SupplierViewModal = React.memo(
    ({ item, open, onClose }: SupplierViewModalProps) => {
        if (!item) return null;

        return (
            <Dialog open={open} onOpenChange={onClose}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Supplier Details</DialogTitle>
                    </DialogHeader>

                    <div className="grid grid-cols-2 gap-6 py-4">
                        <ViewField label="Name" value={item.name} />
                        <ViewField label="Email" value={item.email} />
                        <ViewField label="Phone" value={item.phone} />
                        <ViewField label="Address" value={item.address} />
                        
                        <ViewField 
                            label="Branch" 
                            value={item.branch?.name} 
                        />
                        
                        <ViewField 
                            label="Category" 
                            value={
                                <Badge variant="outline" className="capitalize">
                                    {item.category.replace('_', ' ')}
                                </Badge>
                            } 
                        />
                        
                        <ViewField 
                            label="Status" 
                            value={
                                <Badge 
                                    variant={
                                        item.status === 'active' 
                                            ? 'default' 
                                            : 'destructive'
                                    }
                                >
                                    {item.status === 'active' ? 'Active' : 'Inactive'}
                                </Badge>
                            } 
                        />

                        <ViewField 
                            label="Created At" 
                            value={format(new Date(item.created_at), 'PPP p')} 
                        />
                    </div>

                    <DialogFooter>
                        <Button type="button" onClick={onClose}>
                            Close
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        );
    },
);
