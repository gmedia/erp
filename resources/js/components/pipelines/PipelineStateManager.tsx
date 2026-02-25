import React, { useEffect, useState } from 'react';
import { usePipelineState } from '@/hooks/usePipelineState';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Plus, Trash, Edit, Check, X } from 'lucide-react';
import { PipelineState } from '@/types/pipeline';
import { PipelineStateFormData } from '@/utils/schemas';

interface PipelineStateManagerProps {
    pipelineId: number;
}

export function PipelineStateManager({ pipelineId }: PipelineStateManagerProps) {
    const { states, loading, fetchStates, createState, updateState, deleteState } = usePipelineState(pipelineId);

    const [isCreating, setIsCreating] = useState(false);
    const [editingStateId, setEditingStateId] = useState<number | null>(null);

    const [formData, setFormData] = useState<PipelineStateFormData>({
        code: '',
        name: '',
        type: 'intermediate',
        color: '',
        icon: '',
        description: '',
        sort_order: 0,
    });

    useEffect(() => {
        fetchStates();
    }, [fetchStates]);

    const handleCreateNew = () => {
        setIsCreating(true);
        setEditingStateId(null);
        setFormData({
            code: '',
            name: '',
            type: 'intermediate',
            color: '',
            icon: '',
            description: '',
            sort_order: states.length ? Math.max(...states.map(s => s.sort_order)) + 10 : 0,
        });
    };

    const handleEdit = (state: PipelineState) => {
        setIsCreating(false);
        setEditingStateId(state.id);
        setFormData({
            code: state.code,
            name: state.name,
            type: state.type,
            color: state.color || '',
            icon: state.icon || '',
            description: state.description || '',
            sort_order: state.sort_order,
        });
    };

    const handleCancel = () => {
        setIsCreating(false);
        setEditingStateId(null);
    };

    const handleSave = async () => {
        if (isCreating) {
            const success = await createState(formData);
            if (success) setIsCreating(false);
        } else if (editingStateId) {
            const success = await updateState(editingStateId, formData);
            if (success) setEditingStateId(null);
        }
    };

    const handleDelete = async (stateId: number) => {
        if (window.confirm('Are you sure you want to delete this state?')) {
            await deleteState(stateId);
        }
    };

    const renderRow = (state: PipelineState) => {
        if (editingStateId === state.id) {
            return renderEditRow();
        }

        return (
            <TableRow key={state.id}>
                <TableCell>{state.code}</TableCell>
                <TableCell>{state.name}</TableCell>
                <TableCell>{state.type}</TableCell>
                <TableCell>{state.sort_order}</TableCell>
                <TableCell>
                    <div className="flex gap-2">
                        {state.color && (
                            <div
                                className="w-6 h-6 rounded border border-gray-200"
                                style={{ backgroundColor: state.color }}
                                title={state.color}
                            />
                        )}
                        {state.icon && <span className="text-gray-500">{state.icon}</span>}
                    </div>
                </TableCell>
                <TableCell className="text-right">
                    <Button type="button" variant="ghost" size="icon" onClick={() => handleEdit(state)} title="Edit state">
                        <Edit className="w-4 h-4" />
                    </Button>
                    <Button type="button" variant="ghost" size="icon" onClick={() => handleDelete(state.id)} title="Delete state">
                        <Trash className="w-4 h-4" />
                    </Button>
                </TableCell>
            </TableRow>
        );
    };

    const renderEditRow = () => {
        return (
            <TableRow key="edit-row" className="bg-gray-50 dark:bg-gray-900">
                <TableCell>
                    <Input
                        value={formData.code}
                        onChange={(e) => setFormData({ ...formData, code: e.target.value })}
                        placeholder="Code"
                        className="w-full"
                    />
                </TableCell>
                <TableCell>
                    <Input
                        value={formData.name}
                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                        placeholder="Name"
                        className="w-full"
                    />
                </TableCell>
                <TableCell>
                    <Select
                        value={formData.type}
                        onValueChange={(val: any) => setFormData({ ...formData, type: val })}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="initial">Initial</SelectItem>
                            <SelectItem value="intermediate">Intermediate</SelectItem>
                            <SelectItem value="final">Final</SelectItem>
                        </SelectContent>
                    </Select>
                </TableCell>
                <TableCell>
                    <Input
                        type="number"
                        value={formData.sort_order}
                        onChange={(e) => setFormData({ ...formData, sort_order: parseInt(e.target.value) || 0 })}
                        className="w-20"
                    />
                </TableCell>
                <TableCell>
                    <div className="flex gap-2">
                        <Input
                            value={formData.color || ''}
                            onChange={(e) => setFormData({ ...formData, color: e.target.value })}
                            placeholder="#HEX"
                            className="w-24"
                        />
                        <Input
                            value={formData.icon || ''}
                            onChange={(e) => setFormData({ ...formData, icon: e.target.value })}
                            placeholder="Icon"
                            className="w-24"
                        />
                    </div>
                </TableCell>
                <TableCell className="text-right">
                    <Button type="button" variant="ghost" size="icon" onClick={handleSave} title="Save state">
                        <Check className="w-4 h-4 text-green-600" />
                    </Button>
                    <Button type="button" variant="ghost" size="icon" onClick={handleCancel} title="Cancel edit">
                        <X className="w-4 h-4 text-red-600" />
                    </Button>
                </TableCell>
            </TableRow>
        );
    };

    return (
        <div className="mt-8 pt-6 border-t border-border">
            <div className="flex justify-between items-center mb-4">
                <h3 className="text-lg font-medium">Pipeline States</h3>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={handleCreateNew}
                    disabled={isCreating || editingStateId !== null}
                >
                    <Plus className="w-4 h-4 mr-2" />
                    Add State
                </Button>
            </div>

            <div className="rounded-md border border-border overflow-hidden">
                <Table>
                    <TableHeader className="bg-muted/50">
                        <TableRow>
                            <TableHead>Code</TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead className="w-24">Order</TableHead>
                            <TableHead>UI (Color/Icon)</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {states.map(renderRow)}
                        {isCreating && renderEditRow()}
                        {!states.length && !isCreating && !loading && (
                            <TableRow>
                                <TableCell colSpan={6} className="text-center py-6 text-muted-foreground">
                                    No states added yet. Define the lifecycle stages here.
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
        </div>
    );
}
