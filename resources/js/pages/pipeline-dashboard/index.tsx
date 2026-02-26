import React, { useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { usePipelineDashboard } from '@/hooks/usePipelineDashboard';
import { StateSummaryCards } from '@/components/pipeline-dashboard/StateSummaryCards';
import { StateDistributionChart } from '@/components/pipeline-dashboard/StateDistributionChart';
import { StaleEntitiesTable } from '@/components/pipeline-dashboard/StaleEntitiesTable';
import { PipelineFilter } from '@/components/pipeline-dashboard/PipelineFilter';

interface PipelineDashboardProps {
    pipelines: Array<{
        id: number;
        name: string;
        code: string;
        entity_type: string;
    }>;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pipelines', href: '/pipelines' },
    { title: 'Dashboard', href: '/pipeline-dashboard' },
];

export default function PipelineDashboard({ pipelines }: PipelineDashboardProps) {
    // If there's only one pipeline, pre-select it
    const defaultPipelineId = pipelines.length === 1 ? pipelines[0].id : undefined;

    const { 
        data, 
        isLoading, 
        filters, 
        handleFilterChange 
    } = usePipelineDashboard({
        pipeline_id: defaultPipelineId,
        stale_days: 7
    });

    const onPipelineChange = (val: string) => {
        handleFilterChange('pipeline_id', val === 'all' ? undefined : Number(val));
    };

    const onStaleDaysChange = (val: string) => {
        handleFilterChange('stale_days', Number(val));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pipeline Dashboard" />
            
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 pb-20 max-w-7xl mx-auto w-full">
                <div className="flex flex-col gap-2">
                    <h1 className="text-3xl font-bold tracking-tight">Pipeline Dashboard</h1>
                    <p className="text-muted-foreground">
                        Monitor state distributions and identify bottlenecks across your active pipelines.
                    </p>
                </div>

                <PipelineFilter 
                    pipelines={pipelines}
                    selectedPipelineId={filters.pipeline_id}
                    onChange={onPipelineChange}
                    staleDays={filters.stale_days || 7}
                    onStaleDaysChange={onStaleDaysChange}
                />

                <StateSummaryCards 
                    data={data?.summary || []} 
                    isLoading={isLoading} 
                />

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4 mt-2">
                    <div className="col-span-1 lg:col-span-1">
                        <StateDistributionChart 
                            data={data?.summary || []} 
                            isLoading={isLoading} 
                        />
                    </div>
                    
                    <div className="col-span-1 md:col-span-1 lg:col-span-3">
                        <StaleEntitiesTable 
                            data={data?.stale_entities || []} 
                            isLoading={isLoading}
                            staleDaysThreshold={filters.stale_days || 7}
                        />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
