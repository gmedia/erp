import { FilterDatePicker } from '@/components/common/FilterDatePicker';
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
    return (
        <div className="flex flex-wrap items-center gap-2">
            <FilterDatePicker
                label="As of"
                inlineLabel
                className="w-auto"
                value={asOfDate}
                onChange={(e) => onAsOfDateChange(e.target.value)}
            />

            <div>
                <label htmlFor="branch-select" className="sr-only">
                    Branch
                </label>
                <Select
                    value={branchId?.toString() || 'all'}
                    onValueChange={(value) =>
                        onBranchChange(value === 'all' ? null : Number(value))
                    }
                >
                    <SelectTrigger id="branch-select" className="w-[200px]">
                        <SelectValue placeholder="All Branches" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Branches</SelectItem>
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
