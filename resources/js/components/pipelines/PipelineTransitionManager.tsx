import React, { useEffect, useState } from 'react';
import { usePipelineTransition } from '@/hooks/usePipelineTransition';
import { usePipelineState } from '@/hooks/usePipelineState';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Plus, Trash, Edit } from 'lucide-react';
import { PipelineTransition } from '@/types/pipeline';
import { PipelineTransitionFormDialog } from './PipelineTransitionFormDialog';

interface PipelineTransitionManagerProps {
    pipelineId: number;
}

export function PipelineTransitionManager({ pipelineId }: PipelineTransitionManagerProps) {
    const { transitions, loading, fetchTransitions, deleteTransition } = usePipelineTransition(pipelineId);
    
    // We also need states to show state names
    const { states, fetchStates } = usePipelineState(pipelineId);

    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [editingTransition, setEditingTransition] = useState<PipelineTransition | null>(null);

    useEffect(() => {
        fetchTransitions();
        fetchStates();
    }, [fetchTransitions, fetchStates]);

    const handleCreateNew = () => {
        setEditingTransition(null);
        setIsDialogOpen(true);
    };

    const handleEdit = (transition: PipelineTransition) => {
        setEditingTransition(transition);
        setIsDialogOpen(true);
    };

    const handleDelete = async (transitionId: number) => {
        if (window.confirm('Are you sure you want to delete this transition?')) {
            await deleteTransition(transitionId);
        }
    };

    const renderRow = (transition: PipelineTransition) => {
        return (
            <TableRow key={transition.id}>
                <TableCell>{transition.from_state?.name || transition.from_state_id}</TableCell>
                <TableCell>{transition.to_state?.name || transition.to_state_id}</TableCell>
                <TableCell>{transition.name}</TableCell>
                <TableCell>{transition.code}</TableCell>
                <TableCell>{transition.actions?.length || 0} Actions</TableCell>
                <TableCell className="text-right">
                    <Button type="button" variant="ghost" size="icon" onClick={() => handleEdit(transition)} title="Edit transition">
                        <Edit className="w-4 h-4" />
                    </Button>
                    <Button type="button" variant="ghost" size="icon" onClick={() => handleDelete(transition.id)} title="Delete transition">
                        <Trash className="w-4 h-4" />
                    </Button>
                </TableCell>
            </TableRow>
        );
    };

    return (
        <div className="mt-8 pt-6 border-t border-border">
            <div className="flex justify-between items-center mb-4">
                <h3 className="text-lg font-medium">Pipeline Transitions</h3>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={handleCreateNew}
                >
                    <Plus className="w-4 h-4 mr-2" />
                    Add Transition
                </Button>
            </div>

            <div className="rounded-md border border-border overflow-hidden">
                <Table>
                    <TableHeader className="bg-muted/50">
                        <TableRow>
                            <TableHead>From State</TableHead>
                            <TableHead>To State</TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead>Code</TableHead>
                            <TableHead>Actions</TableHead>
                            <TableHead className="text-right">Manage</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {transitions.map(renderRow)}
                        {!transitions.length && !loading && (
                            <TableRow>
                                <TableCell colSpan={6} className="text-center py-6 text-muted-foreground">
                                    No transitions defined. Add one to enable state movement.
                                </TableCell>
                            </TableRow>
                        )}
                        {loading && (
                            <TableRow>
                                <TableCell colSpan={6} className="text-center py-6">
                                    Loading...
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            {isDialogOpen && (
                <PipelineTransitionFormDialog
                    open={isDialogOpen}
                    onOpenChange={setIsDialogOpen}
                    pipelineId={pipelineId}
                    transition={editingTransition}
                    states={states}
                    onSuccess={() => {
                        fetchTransitions();
                        setIsDialogOpen(false);
                    }}
                />
            )}
        </div>
    );
}
