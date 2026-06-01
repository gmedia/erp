import { Input } from '@/components/ui/input';
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
        <div className="flex flex-col items-start gap-3 sm:flex-row sm:items-center">
            <div className="flex flex-col gap-1.5">
                <label
                    htmlFor="as-of-date-input"
                    className="text-sm font-medium text-muted-foreground"
                >
                    As of Date
                </label>
                <Input
                    id="as-of-date-input"
                    type="date"
                    value={asOfDate}
                    onChange={(e) => onAsOfDateChange(e.target.value)}
                    className="w-[200px]"
                />
            </div>

            <div className="flex flex-col gap-1.5">
                <label
                    htmlFor="branch-select"
                    className="text-sm font-medium text-muted-foreground"
                >
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
