import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { StateSummary } from '@/hooks/usePipelineDashboard';

interface StateSummaryCardsProps {
    data: StateSummary[];
    isLoading: boolean;
}

export function StateSummaryCards({
    data,
    isLoading,
}: Readonly<StateSummaryCardsProps>) {
    if (isLoading) {
        return (
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                {['state-1', 'state-2', 'state-3', 'state-4'].map((key) => (
                    <Card key={key} className="animate-pulse">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="rounded bg-muted text-sm font-medium text-transparent">
                                Loading State...
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-8 w-12 rounded bg-muted text-2xl font-bold text-transparent">
                                0
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        );
    }

    if (!data || data.length === 0) {
        return (
            <div className="w-full rounded-lg border border-dashed p-8 text-center text-muted-foreground">
                No state data available for the selected pipeline.
            </div>
        );
    }

    const totalEntities = data.reduce((sum, state) => sum + state.count, 0);
    if (totalEntities === 0) {
        return (
            <div className="w-full rounded-lg border border-dashed p-8 text-center text-muted-foreground">
                <p className="font-medium text-foreground">
                    No entities in any state yet
                </p>
                <p className="mt-1 text-sm">
                    State distribution appears once records enter these
                    pipelines.
                </p>
            </div>
        );
    }

    const groups = new Map<
        string,
        { pipelineName: string | null; states: StateSummary[] }
    >();
    for (const state of data) {
        const key = state.pipeline_id ? String(state.pipeline_id) : 'all';
        const group = groups.get(key) ?? {
            pipelineName: state.pipeline_name ?? null,
            states: [],
        };
        group.states.push(state);
        groups.set(key, group);
    }
    const showGroupHeading = groups.size > 1;

    // Grup yang seluruh kartunya 0 tidak ditampilkan (W5-12); bila semua grup
    // kosong, tampilkan empty state di bawah.
    const visibleGroups = Array.from(groups.entries()).filter(([, group]) =>
        group.states.some((state) => state.count > 0),
    );

    if (visibleGroups.length === 0) {
        return (
            <div className="w-full rounded-lg border border-dashed p-8 text-center text-muted-foreground">
                <p className="font-medium text-foreground">
                    No entities in any state yet
                </p>
                <p className="mt-1 text-sm">
                    State distribution appears once records enter these
                    pipelines.
                </p>
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-4">
            {visibleGroups.map(([key, group]) => (
                <div key={key} className="flex flex-col gap-2">
                    {showGroupHeading && group.pipelineName ? (
                        <h2 className="text-sm font-semibold text-foreground">
                            {group.pipelineName}
                        </h2>
                    ) : null}
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                        {group.states.map((state) => (
                            <Card
                                key={state.state_id}
                                className="gap-0 overflow-hidden py-0"
                            >
                                <div
                                    className="h-1.5 w-full"
                                    style={{ backgroundColor: state.color }}
                                />
                                <CardHeader className="flex flex-row items-center justify-between pt-4 pb-2">
                                    <CardTitle className="text-sm font-medium text-muted-foreground">
                                        {state.name}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-bold">
                                        {state.count}
                                    </div>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        entities currently in state
                                    </p>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>
            ))}
        </div>
    );
}
