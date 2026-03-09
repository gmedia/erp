import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { type Pipeline } from '@/types/entity';

interface PipelineViewModalProps {
    open: boolean;
    onClose: () => void;
    item: Pipeline | null;
}

export function PipelineViewModal({
    open,
    onClose,
    item,
}: PipelineViewModalProps) {
    if (!item) return null;

    return (
        <Dialog open={open} onOpenChange={onClose}>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{item.name}</DialogTitle>
                    <DialogDescription>
                        View complete pipeline information for the selected
                        item.
                    </DialogDescription>
                </DialogHeader>
                <div className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <span className="block text-sm font-semibold text-gray-500">
                                Code
                            </span>
                            <span>{item.code}</span>
                        </div>
                        <div>
                            <span className="block text-sm font-semibold text-gray-500">
                                Entity Type
                            </span>
                            <span>{item.entity_type}</span>
                        </div>
                        <div>
                            <span className="block text-sm font-semibold text-gray-500">
                                Version
                            </span>
                            <span>{item.version}</span>
                        </div>
                        <div>
                            <span className="block text-sm font-semibold text-gray-500">
                                Status
                            </span>
                            <span
                                className={
                                    item.is_active
                                        ? 'font-medium text-green-600'
                                        : 'font-medium text-red-600'
                                }
                            >
                                {item.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                        <div>
                            <span className="block text-sm font-semibold text-gray-500">
                                Created By
                            </span>
                            <span>{item.created_by?.name || 'System'}</span>
                        </div>
                        <div>
                            <span className="block text-sm font-semibold text-gray-500">
                                Created At
                            </span>
                            <span>
                                {new Date(item.created_at).toLocaleDateString()}
                            </span>
                        </div>
                    </div>
                    {item.description && (
                        <div>
                            <span className="block text-sm font-semibold text-gray-500">
                                Description
                            </span>
                            <p className="mt-1 whitespace-pre-wrap">
                                {item.description}
                            </p>
                        </div>
                    )}
                    {item.conditions && (
                        <div>
                            <span className="block text-sm font-semibold text-gray-500">
                                Conditions (JSON)
                            </span>
                            <pre className="mt-1 overflow-x-auto rounded-md bg-gray-100 p-3 text-sm whitespace-pre-wrap">
                                {item.conditions}
                            </pre>
                        </div>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
