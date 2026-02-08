'use client';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type AssetMovement } from '@/components/asset-movements/AssetMovementColumns';
import { format } from 'date-fns';

interface AssetMovementViewModalProps {
    open: boolean;
    onClose: () => void;
    item: AssetMovement | null;
}

export function AssetMovementViewModal({ open, onClose, item }: AssetMovementViewModalProps) {
    if (!item) return null;

    return (
        <Card className="border-none shadow-none">
            <CardHeader>
                <CardTitle>Movement Details</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span className="text-muted-foreground block text-xs">Asset</span>
                        <span className="font-medium">{item.asset.name} ({item.asset.asset_code})</span>
                    </div>
                    <div>
                        <span className="text-muted-foreground block text-xs">Type</span>
                        <span className="capitalize">{item.movement_type}</span>
                    </div>
                    <div>
                        <span className="text-muted-foreground block text-xs">Date</span>
                        <span>{format(new Date(item.moved_at), 'PPP p')}</span>
                    </div>
                    <div>
                        <span className="text-muted-foreground block text-xs">Reference</span>
                        <span>{item.reference || '-'}</span>
                    </div>
                </div>
                
                <hr />
                
                <div className="grid grid-cols-2 gap-4 text-sm">
                    <div className="space-y-2">
                        <span className="text-muted-foreground font-medium block text-xs">Origin</span>
                        <div className="bg-muted/50 p-2 rounded">
                            {item.from_branch && <div>{item.from_branch}</div>}
                            {item.from_location && <div className="text-xs text-muted-foreground">{item.from_location}</div>}
                            {item.from_employee && <div className="text-xs text-primary">{item.from_employee}</div>}
                            {!item.from_branch && !item.from_employee && <span className="text-muted-foreground text-xs italic">Initial/Acquired</span>}
                        </div>
                    </div>
                    <div className="space-y-2">
                        <span className="text-muted-foreground font-medium block text-xs">Destination</span>
                        <div className="bg-primary/5 p-2 rounded">
                            {item.to_branch && <div>{item.to_branch}</div>}
                            {item.to_location && <div className="text-xs text-muted-foreground">{item.to_location}</div>}
                            {item.to_employee && <div className="text-xs text-primary">{item.to_employee}</div>}
                        </div>
                    </div>
                </div>

                {item.notes && (
                    <div className="mt-4">
                        <span className="text-muted-foreground block text-xs mb-1">Notes</span>
                        <p className="text-sm bg-muted/30 p-2 rounded border">{item.notes}</p>
                    </div>
                )}
                
                <div className="mt-4 text-xs text-muted-foreground flex justify-between">
                    <span>Recorded by: {item.created_by}</span>
                </div>
            </CardContent>
        </Card>
    );
}
