import React from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';

interface PipelineFilterProps {
    pipelines: Array<{ id: number; name: string }>;
    selectedPipelineId?: number | string;
    onChange: (pipelineId: string) => void;
    staleDays: number;
    onStaleDaysChange: (days: string) => void;
}

export function PipelineFilter({ 
    pipelines, 
    selectedPipelineId, 
    onChange,
    staleDays,
    onStaleDaysChange
}: PipelineFilterProps) {
    return (
        <Card className="mb-6 shadow-sm">
            <CardContent className="flex flex-col sm:flex-row gap-6 p-4 items-end">
                <div className="space-y-2 w-full sm:w-64">
                    <Label htmlFor="pipeline-filter" className="text-xs font-semibold uppercase text-muted-foreground">
                        Select Pipeline
                    </Label>
                    <Select 
                        value={selectedPipelineId?.toString() || 'all'} 
                        onValueChange={onChange}
                    >
                        <SelectTrigger id="pipeline-filter" className="border-gray-300">
                            <SelectValue placeholder="All Active Pipelines" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Active Pipelines</SelectItem>
                            {pipelines.map(pipeline => (
                                <SelectItem key={pipeline.id} value={pipeline.id.toString()}>
                                    {pipeline.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="space-y-2 w-full sm:w-48">
                    <Label htmlFor="stale-days" className="text-xs font-semibold uppercase text-muted-foreground">
                        Stale Threshold
                    </Label>
                    <Select 
                        value={staleDays.toString()} 
                        onValueChange={onStaleDaysChange}
                    >
                        <SelectTrigger id="stale-days" className="border-gray-300">
                            <SelectValue placeholder="Select days" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="3">3 Days</SelectItem>
                            <SelectItem value="7">7 Days</SelectItem>
                            <SelectItem value="14">14 Days</SelectItem>
                            <SelectItem value="30">30 Days</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </CardContent>
        </Card>
    );
}
