import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { StaleEntity } from '@/hooks/usePipelineDashboard';
import { formatDistanceToNow, parseISO } from 'date-fns';
import { AlertCircle, Clock } from 'lucide-react';

interface StaleEntitiesTableProps {
    data: StaleEntity[];
    isLoading: boolean;
    staleDaysThreshold: number;
}

export function StaleEntitiesTable({
    data,
    isLoading,
    staleDaysThreshold,
}: StaleEntitiesTableProps) {
    if (isLoading) {
        return (
            <Card className="col-span-1 md:col-span-2 lg:col-span-3">
                <CardHeader>
                    <CardTitle className="w-48 animate-pulse rounded bg-muted text-transparent">
                        Loading...
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="space-y-4">
                        {[...Array(3)].map((_, i) => (
                            <div
                                key={i}
                                className="h-12 animate-pulse rounded bg-muted"
                            />
                        ))}
                    </div>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card className="col-span-1 md:col-span-2 lg:col-span-3">
            <CardHeader>
                <div className="flex items-center justify-between">
                    <div>
                        <CardTitle className="flex items-center gap-2 text-amber-600 dark:text-amber-400">
                            <AlertCircle className="h-5 w-5" />
                            Stale Entities
                        </CardTitle>
                        <CardDescription>
                            Entities stuck in intermediate states for more than{' '}
                            {staleDaysThreshold} days
                        </CardDescription>
                    </div>
                    <div>
                        <Badge
                            variant="outline"
                            className="border-amber-300 bg-amber-50 font-mono text-xs text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-400"
                        >
                            {data.length} {data.length === 50 ? '+' : ''}{' '}
                            detected
                        </Badge>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                {data.length === 0 ? (
                    <div className="flex flex-col items-center justify-center rounded-lg border border-dashed bg-muted/50 p-8 text-center text-muted-foreground">
                        <Clock className="mb-2 h-8 w-8 text-muted-foreground/60" />
                        <p>No stale entities found.</p>
                        <p className="text-sm">
                            Everything is moving smoothly through the pipeline.
                        </p>
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Entity</TableHead>
                                    <TableHead>Current State</TableHead>
                                    <TableHead>Last Transition</TableHead>
                                    <TableHead className="text-right">
                                        Days Stale
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {data.map((entity) => (
                                    <TableRow key={entity.id}>
                                        <TableCell className="text-xs font-medium text-muted-foreground">
                                            {entity.entity_type}
                                        </TableCell>
                                        <TableCell>
                                            {entity.entity_name}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant="outline"
                                                style={{
                                                    borderColor:
                                                        entity.current_state
                                                            .color,
                                                    color: entity.current_state
                                                        .color,
                                                }}
                                            >
                                                {entity.current_state.name}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-col">
                                                <span
                                                    className="text-sm"
                                                    title={
                                                        entity.last_transitioned_at
                                                    }
                                                >
                                                    {formatDistanceToNow(
                                                        parseISO(
                                                            entity.last_transitioned_at,
                                                        ),
                                                        { addSuffix: true },
                                                    )}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    by{' '}
                                                    {
                                                        entity.last_transitioned_by
                                                    }
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-right font-bold text-amber-600 dark:text-amber-400">
                                            {entity.days_in_state}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
