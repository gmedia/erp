import { useTranslatableText } from '@/components/common/TranslatedText';
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
import { useTranslation } from '@/contexts/i18n-context';
import { StaleEntity } from '@/hooks/usePipelineDashboard';
import { formatDistanceToNow, parseISO } from 'date-fns';
import { AlertCircle, Clock } from 'lucide-react';

const STALE_ENTITY_SKELETON_ROWS = [1, 2, 3] as const;

interface StaleEntitiesTableProps {
    readonly data: StaleEntity[];
    readonly isLoading: boolean;
    readonly staleDaysThreshold: number;
}

export function StaleEntitiesTable({
    data,
    isLoading,
    staleDaysThreshold,
}: StaleEntitiesTableProps) {
    const { t } = useTranslation();
    const loadingLabel = useTranslatableText({
        key: 'common.loading',
        fallback: 'Loading...',
    });
    const staleEntitiesLabel = useTranslatableText({
        key: 'pipelineDashboard.staleEntities',
        fallback: 'Stale Entities',
    });
    const detectedLabel = useTranslatableText({
        key: 'pipelineDashboard.detected',
        fallback: 'detected',
    });
    const noStaleLabel = useTranslatableText({
        key: 'pipelineDashboard.noStaleEntities',
        fallback: 'No stale entities found.',
    });
    const smoothLabel = useTranslatableText({
        key: 'pipelineDashboard.allMovingSmoothly',
        fallback: 'Everything is moving smoothly through the pipeline.',
    });
    const typeLabel = useTranslatableText({
        key: 'pipelineDashboard.type',
        fallback: 'Type',
    });
    const entityLabel = useTranslatableText({
        key: 'pipelineDashboard.entity',
        fallback: 'Entity',
    });
    const currentStateLabel = useTranslatableText({
        key: 'pipelineDashboard.currentState',
        fallback: 'Current State',
    });
    const lastTransitionLabel = useTranslatableText({
        key: 'pipelineDashboard.lastTransition',
        fallback: 'Last Transition',
    });
    const daysStaleLabel = useTranslatableText({
        key: 'pipelineDashboard.daysStale',
        fallback: 'Days Stale',
    });
    const byLabel = useTranslatableText({
        key: 'pipelineDashboard.by',
        fallback: 'by',
    });
    const staleDescriptionKey = 'pipelineDashboard.staleDescription';
    const staleDescriptionTranslated = t(staleDescriptionKey, {
        days: String(staleDaysThreshold),
    });
    const staleDescriptionText =
        staleDescriptionTranslated === staleDescriptionKey
            ? `Entities stuck in intermediate states for more than ${staleDaysThreshold} days`
            : staleDescriptionTranslated;

    if (isLoading) {
        return (
            <Card className="col-span-1 md:col-span-2 lg:col-span-3">
                <CardHeader>
                    <CardTitle className="w-48 animate-pulse rounded bg-muted text-transparent">
                        {loadingLabel}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="space-y-4">
                        {STALE_ENTITY_SKELETON_ROWS.map((rowNumber) => (
                            <div
                                key={`stale-entity-skeleton-${rowNumber}`}
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
                        <CardTitle className="flex items-center gap-2 text-warning">
                            <AlertCircle className="h-5 w-5" />
                            {staleEntitiesLabel}
                        </CardTitle>
                        <CardDescription>
                            {staleDescriptionText}
                        </CardDescription>
                    </div>
                    <div>
                        <Badge
                            variant="outline"
                            className="border-warning/30 bg-warning-soft font-mono text-xs text-warning"
                        >
                            {data.length} {data.length === 50 ? '+' : ''}{' '}
                            {detectedLabel}
                        </Badge>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                {data.length === 0 ? (
                    <div className="flex flex-col items-center justify-center rounded-lg border border-dashed bg-muted/50 p-8 text-center text-muted-foreground">
                        <Clock className="mb-2 h-8 w-8 text-muted-foreground/60" />
                        <p>{noStaleLabel}</p>
                        <p className="text-sm">{smoothLabel}</p>
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{typeLabel}</TableHead>
                                    <TableHead>{entityLabel}</TableHead>
                                    <TableHead>{currentStateLabel}</TableHead>
                                    <TableHead>{lastTransitionLabel}</TableHead>
                                    <TableHead className="text-right">
                                        {daysStaleLabel}
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
                                                    {byLabel}{' '}
                                                    {
                                                        entity.last_transitioned_by
                                                    }
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-right font-bold text-warning">
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
