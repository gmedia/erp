import { PipelineFilter } from '@/components/pipeline-dashboard/PipelineFilter';
import { StaleEntitiesTable } from '@/components/pipeline-dashboard/StaleEntitiesTable';
import { StateDistributionChart } from '@/components/pipeline-dashboard/StateDistributionChart';
import { StateSummaryCards } from '@/components/pipeline-dashboard/StateSummaryCards';
import { usePipelineDashboard } from '@/hooks/usePipelineDashboard';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Helmet } from 'react-helmet-async';

import axios from '@/lib/axios';
import { useQuery } from '@tanstack/react-query';
import { Loader2 } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pipelines', href: '/pipelines' },
    { title: 'Dashboard', href: '/pipeline-dashboard' },
];

export default function PipelineDashboard() {
    const { data: pipelinesResponse, isLoading: pipelinesLoading } = useQuery({
        queryKey: ['pipelines-list'],
        queryFn: async () => {
            const res = await axios.get('/api/pipelines?per_page=100');
            return res.data;
        },
    });

    const pipelines = Array.isArray(pipelinesResponse?.data)
        ? pipelinesResponse.data
        : [];

    // If there's only one pipeline, pre-select it
    const defaultPipelineId =
        pipelines.length === 1 ? pipelines[0].id : undefined;

    const { data, isLoading, filters, handleFilterChange } =
        usePipelineDashboard({
            pipeline_id: defaultPipelineId,
            stale_days: 7,
        });

    if (pipelinesLoading) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Helmet>
                    <title>
                        Pipeline Dashboard -{' '}
                        {import.meta.env.VITE_APP_NAME || 'ERP'}
                    </title>
                </Helmet>
                <div className="flex h-full items-center justify-center p-4">
                    <Loader2 className="mr-2 h-6 w-6 animate-spin text-muted-foreground" />
                    <span>Loading pipelines...</span>
                </div>
            </AppLayout>
        );
    }

    const onPipelineChange = (val: string) => {
        handleFilterChange(
            'pipeline_id',
            val === 'all' ? undefined : Number(val),
        );
    };

    const onStaleDaysChange = (val: string) => {
        handleFilterChange('stale_days', Number(val));
    };

    const summaryData = Array.isArray(data?.summary) ? data.summary : [];
    const staleEntitiesData = Array.isArray(data?.stale_entities)
        ? data.stale_entities
        : [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Helmet>
                <title>
                    Pipeline Dashboard -{' '}
                    {import.meta.env.VITE_APP_NAME || 'ERP'}
                </title>
            </Helmet>

            <div className="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-6 p-4 pb-20 md:p-6">
                <div className="flex flex-col gap-2">
                    <h1 className="text-3xl font-bold tracking-tight">
                        Pipeline Dashboard
                    </h1>
                    <p className="text-muted-foreground">
                        Monitor state distributions and identify bottlenecks
                        across your active pipelines.
                    </p>
                </div>

                <PipelineFilter
                    pipelines={pipelines}
                    selectedPipelineId={filters.pipeline_id}
                    onChange={onPipelineChange}
                    staleDays={filters.stale_days || 7}
                    onStaleDaysChange={onStaleDaysChange}
                />

                <StateSummaryCards data={summaryData} isLoading={isLoading} />

                <div className="mt-2 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <div className="col-span-1 lg:col-span-1">
                        <StateDistributionChart
                            data={summaryData}
                            isLoading={isLoading}
                        />
                    </div>

                    <div className="col-span-1 md:col-span-1 lg:col-span-3">
                        <StaleEntitiesTable
                            data={staleEntitiesData}
                            isLoading={isLoading}
                            staleDaysThreshold={filters.stale_days || 7}
                        />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
