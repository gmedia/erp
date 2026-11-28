import { FilterDatePicker } from '@/components/common/FilterDatePicker';
import { useTranslatableText } from '@/components/common/TranslatedText';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { memo } from 'react';
import type { BranchOption } from '../../hooks/useAgingDashboard';

interface AgingFiltersProps {
    readonly asOfDate: string;
    readonly branchId: number | null;
    readonly branches: BranchOption[];
    readonly onAsOfDateChange: (date: string) => void;
    readonly onBranchChange: (branchId: number | null) => void;
}

export const AgingFilters = memo<AgingFiltersProps>(function AgingFilters({
    asOfDate,
    branchId,
    branches,
    onAsOfDateChange,
    onBranchChange,
}) {
    const asOfLabel = useTranslatableText({
        key: 'filter_field.as_of',
        fallback: 'As of',
    });
    const branchLabel = useTranslatableText({
        key: 'filter_field.branch',
        fallback: 'Branch',
    });
    const allBranches = useTranslatableText({
        key: 'filter.all_branches',
        fallback: 'All Branches',
    });

    return (
        <div className="flex flex-nowrap items-center gap-2">
            <FilterDatePicker
                label={asOfLabel}
                inlineLabel
                className="w-auto shrink-0"
                value={asOfDate}
                onChange={(e) => onAsOfDateChange(e.target.value)}
            />

            <div className="shrink-0">
                <label htmlFor="branch-select" className="sr-only">
                    {branchLabel}
                </label>
                <Select
                    value={branchId?.toString() || 'all'}
                    onValueChange={(value) =>
                        onBranchChange(value === 'all' ? null : Number(value))
                    }
                >
                    <SelectTrigger id="branch-select" className="w-[180px]">
                        <SelectValue placeholder={allBranches} />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">{allBranches}</SelectItem>
                        {branches.map((branch) => (
                            <SelectItem
                                key={branch.id}
                                value={branch.id.toString()}
                            >
                                {branch.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
        </div>
    );
});
