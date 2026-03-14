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

    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            {data.map((state) => (
                <Card
                    key={state.state_id}
                    className="overflow-hidden transition-all hover:shadow-md"
                >
                    <div
                        className="h-2 w-full"
                        style={{ backgroundColor: state.color }}
                    />
                    <CardHeader className="flex flex-row items-center justify-between pt-4 pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            {state.name}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-3xl font-bold">{state.count}</div>
                        <p className="mt-1 text-xs text-muted-foreground">
                            entities currently in state
                        </p>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}
