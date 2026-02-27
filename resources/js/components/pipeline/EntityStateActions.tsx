import { useEffect, useState } from 'react';
import { useEntityPipeline, PipelineTransition } from '@/hooks/useEntityPipeline';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { 
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Textarea } from '@/components/ui/textarea';
import * as LucideIcons from 'lucide-react';
import { Loader2 } from 'lucide-react';

interface Props {
    entityType: string;
    entityId: string | number;
    onStateChange?: () => void; // Optional callback when state changes to refresh parent data
}

export function EntityStateActions({ entityType, entityId, onStateChange }: Props) {
    const { 
        stateData, 
        loading, 
        fetchState, 
        executeTransition 
    } = useEntityPipeline(entityType, entityId);

    const [selectedTransition, setSelectedTransition] = useState<PipelineTransition | null>(null);
    const [showConfirm, setShowConfirm] = useState(false);
    const [showCommentModal, setShowCommentModal] = useState(false);
    const [comment, setComment] = useState('');
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        fetchState();
    }, [fetchState]);

    const handleActionClick = (transition: PipelineTransition) => {
        setSelectedTransition(transition);
        setComment('');
        
        if (transition.requires_comment) {
            setShowCommentModal(true);
        } else if (transition.requires_confirmation) {
            setShowConfirm(true);
        } else {
            handleExecute(transition);
        }
    };

    const handleExecute = async (transition: PipelineTransition, confirmComment?: string) => {
        setProcessing(true);
        const success = await executeTransition(transition.id, confirmComment);
        setProcessing(false);
        
        if (success) {
            setShowConfirm(false);
            setShowCommentModal(false);
            if (onStateChange) onStateChange();
        }
    };

    if (loading && !stateData) {
        return <Skeleton className="h-8 w-64" />;
    }

    if (!stateData) {
        return null; // Don't render if no pipeline is configured for this entity
    }

    const { current_state, available_transitions } = stateData;
    
    // Dynamically render the icon
    const IconComponent = current_state.icon ? (LucideIcons as any)[current_state.icon] : null;

    return (
        <div className="flex flex-wrap items-center gap-3">
            <Badge 
                variant="outline"
                className="px-3 md:py-1 md:text-sm font-medium border rounded-md" 
                style={{ 
                    backgroundColor: `${current_state.color}15`, 
                    color: current_state.color,
                    borderColor: `${current_state.color}40`
                }}
            >
                <div className="flex items-center gap-1.5">
                    {IconComponent && <IconComponent className="w-3.5 h-3.5" />}
                    {current_state.name}
                </div>
            </Badge>

            {available_transitions && available_transitions.length > 0 && (
                <div className="flex items-center gap-2 border-l pl-3 ml-1">
                    <TooltipProvider>
                        {available_transitions.map((transition) => (
                            <Tooltip key={transition.id}>
                                <TooltipTrigger asChild>
                                    <div>
                                        <Button
                                            size="sm"
                                            variant={transition.is_allowed ? "default" : "secondary"}
                                            disabled={!transition.is_allowed || processing}
                                            onClick={() => handleActionClick(transition)}
                                            style={transition.is_allowed && transition.to_state.color ? {
                                                backgroundColor: transition.to_state.color,
                                                color: 'white'
                                            } : undefined}
                                            className="h-8 shadow-sm cursor-pointer"
                                        >
                                            {processing && selectedTransition?.id === transition.id && (
                                                <Loader2 className="mr-2 h-3.5 w-3.5 animate-spin" />
                                            )}
                                            {transition.name}
                                        </Button>
                                    </div>
                                </TooltipTrigger>
                                {!transition.is_allowed && (
                                    <TooltipContent className="max-w-xs text-xs text-red-500 bg-red-50 border-red-200">
                                        <div className="font-semibold mb-1 text-red-700">Cannot execute:</div>
                                        <ul className="list-disc pl-3">
                                            {transition.rejection_reasons.map((reason, i) => (
                                                <li key={i}>{reason}</li>
                                            ))}
                                        </ul>
                                    </TooltipContent>
                                )}
                            </Tooltip>
                        ))}
                    </TooltipProvider>
                </div>
            )}

            {/* Confirmation Dialog */}
            <AlertDialog open={showConfirm} onOpenChange={setShowConfirm}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Confirm Action</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to execute "{selectedTransition?.name}"? 
                            This will change the status to {selectedTransition?.to_state.name}.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel disabled={processing}>Cancel</AlertDialogCancel>
                        <AlertDialogAction 
                            onClick={(e) => {
                                e.preventDefault();
                                if (selectedTransition) handleExecute(selectedTransition);
                            }}
                            disabled={processing}
                            style={selectedTransition?.to_state.color ? {
                                backgroundColor: selectedTransition?.to_state.color,
                                color: 'white'
                            } : undefined}
                        >
                            {processing ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
                            Confirm
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* Comment Dialog */}
            <Dialog open={showCommentModal} onOpenChange={setShowCommentModal}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{selectedTransition?.name}</DialogTitle>
                        <DialogDescription>
                            Please provide a reason or comment for this action. This is required.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4 py-4">
                        <Textarea 
                            placeholder="Enter your comment here..."
                            value={comment}
                            onChange={(e) => setComment(e.target.value)}
                            rows={4}
                            autoFocus
                        />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowCommentModal(false)} disabled={processing}>
                            Cancel
                        </Button>
                        <Button 
                            disabled={comment.trim().length === 0 || processing}
                            onClick={() => selectedTransition && handleExecute(selectedTransition, comment)}
                            style={selectedTransition?.to_state.color ? {
                                backgroundColor: selectedTransition?.to_state.color,
                                color: 'white'
                            } : undefined}
                        >
                            {processing ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
                            Submit
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
