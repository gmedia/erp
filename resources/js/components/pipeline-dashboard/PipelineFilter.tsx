import { AsyncSelect } from '@/components/common/AsyncSelect';
import { useTranslatableText } from '@/components/common/TranslatedText';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/contexts/i18n-context';

const STALE_DAY_OPTIONS = [3, 7, 14, 30] as const;

interface PipelineFilterProps {
    pipelines: Array<{ id: number; name: string }>;
    selectedPipelineId?: number | string;
    onChange: (pipelineId: string) => void;
    staleDays: number;
    onStaleDaysChange: (days: string) => void;
    selectedBranchId?: number | string;
    onBranchChange: (branchId: string) => void;
}

export function PipelineFilter({
    pipelines,
    selectedPipelineId,
    onChange,
    staleDays,
    onStaleDaysChange,
    selectedBranchId,
    onBranchChange,
}: Readonly<PipelineFilterProps>) {
    const { t } = useTranslation();
    const daysLabel = (days: number) => {
        const key = 'pipelineDashboard.daysCount';
        const translated = t(key, { count: String(days) });
        return translated === key ? `${days} Days` : translated;
    };
    const branchLabel = useTranslatableText({
        key: 'pipelineDashboard.branch',
        fallback: 'Branch',
    });
    const allBranchesLabel = useTranslatableText({
        key: 'pipelineDashboard.allBranches',
        fallback: 'All Branches',
    });
    const selectPipelineLabel = useTranslatableText({
        key: 'pipelineDashboard.selectPipeline',
        fallback: 'Select Pipeline',
    });
    const allActivePipelinesLabel = useTranslatableText({
        key: 'pipelineDashboard.allActivePipelines',
        fallback: 'All Active Pipelines',
    });
    const staleThresholdLabel = useTranslatableText({
        key: 'pipelineDashboard.staleThreshold',
        fallback: 'Stale Threshold',
    });
    const selectDaysLabel = useTranslatableText({
        key: 'pipelineDashboard.selectDays',
        fallback: 'Select days',
    });

    return (
        <Card>
            <CardContent className="flex flex-col items-end gap-6 p-4 sm:flex-row">
                <div className="w-full space-y-2 sm:w-64">
                    <Label
                        htmlFor="branch-filter"
                        className="text-sm font-medium text-muted-foreground"
                    >
                        {branchLabel}
                    </Label>
                    <AsyncSelect
                        url="/api/branches"
                        placeholder={allBranchesLabel}
                        value={
                            selectedBranchId
                                ? String(selectedBranchId)
                                : undefined
                        }
                        onValueChange={onBranchChange}
                    />
                </div>

                <div className="w-full space-y-2 sm:w-64">
                    <Label
                        htmlFor="pipeline-filter"
                        className="text-sm font-medium text-muted-foreground"
                    >
                        {selectPipelineLabel}
                    </Label>
                    <Select
                        value={selectedPipelineId?.toString() || 'all'}
                        onValueChange={onChange}
                    >
                        <SelectTrigger id="pipeline-filter">
                            <SelectValue
                                placeholder={allActivePipelinesLabel}
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                {allActivePipelinesLabel}
                            </SelectItem>
                            {pipelines.map((pipeline) => (
                                <SelectItem
                                    key={pipeline.id}
                                    value={pipeline.id.toString()}
                                >
                                    {pipeline.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="w-full space-y-2 sm:w-48">
                    <Label
                        htmlFor="stale-days"
                        className="text-sm font-medium text-muted-foreground"
                    >
                        {staleThresholdLabel}
                    </Label>
                    <Select
                        value={staleDays.toString()}
                        onValueChange={onStaleDaysChange}
                    >
                        <SelectTrigger id="stale-days">
                            <SelectValue placeholder={selectDaysLabel} />
                        </SelectTrigger>
                        <SelectContent>
                            {STALE_DAY_OPTIONS.map((days) => (
                                <SelectItem key={days} value={String(days)}>
                                    {daysLabel(days)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </CardContent>
        </Card>
    );
}
