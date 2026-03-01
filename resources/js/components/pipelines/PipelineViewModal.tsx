import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { type Pipeline } from '@/types/entity';

interface PipelineViewModalProps {
    open: boolean;
    onClose: () => void;
    item: Pipeline | null;
}

export function PipelineViewModal({ open, onClose, item }: PipelineViewModalProps) {
    if (!item) return null;

    return (
        <Dialog open={open} onOpenChange={onClose}>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{item.name}</DialogTitle>
                    <DialogDescription>
                        View complete pipeline information for the selected item.
                    </DialogDescription>
                </DialogHeader>
                <div className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <span className="font-semibold block text-gray-500 text-sm">Code</span>
                            <span>{item.code}</span>
                        </div>
                        <div>
                            <span className="font-semibold block text-gray-500 text-sm">Entity Type</span>
                            <span>{item.entity_type}</span>
                        </div>
                        <div>
                            <span className="font-semibold block text-gray-500 text-sm">Version</span>
                            <span>{item.version}</span>
                        </div>
                        <div>
                            <span className="font-semibold block text-gray-500 text-sm">Status</span>
                            <span className={item.is_active ? 'text-green-600 font-medium' : 'text-red-600 font-medium'}>
                                {item.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                        <div>
                            <span className="font-semibold block text-gray-500 text-sm">Created By</span>
                            <span>{item.created_by?.name || 'System'}</span>
                        </div>
                        <div>
                            <span className="font-semibold block text-gray-500 text-sm">Created At</span>
                            <span>{new Date(item.created_at).toLocaleDateString()}</span>
                        </div>
                    </div>
                    {item.description && (
                        <div>
                            <span className="font-semibold block text-gray-500 text-sm">Description</span>
                            <p className="whitespace-pre-wrap mt-1">{item.description}</p>
                        </div>
                    )}
                    {item.conditions && (
                        <div>
                            <span className="font-semibold block text-gray-500 text-sm">Conditions (JSON)</span>
                            <pre className="bg-gray-100 p-3 rounded-md mt-1 text-sm overflow-x-auto whitespace-pre-wrap">
                                {item.conditions}
                            </pre>
                        </div>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
