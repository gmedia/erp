import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { memo } from 'react';
import type { FiscalYear } from '../../hooks/useFinancialDashboard';

interface FiscalYearSelectorProps {
    readonly fiscalYears: FiscalYear[];
    readonly selectedYearId: number | null;
    readonly comparisonYearId: number | null;
    readonly onYearChange: (yearId: number | null) => void;
    readonly onComparisonYearChange: (yearId: number | null) => void;
}

export const FiscalYearSelector = memo<FiscalYearSelectorProps>(
    function FiscalYearSelector({
        fiscalYears,
        selectedYearId,
        comparisonYearId,
        onYearChange,
        onComparisonYearChange,
    }) {
        return (
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div className="flex flex-col gap-1.5">
                    <label
                        htmlFor="fiscal-year-select"
                        className="text-sm font-medium text-muted-foreground"
                    >
                        Fiscal Year
                    </label>
                    <Select
                        value={selectedYearId?.toString() || ''}
                        onValueChange={(value) =>
                            onYearChange(value ? Number(value) : null)
                        }
                    >
                        <SelectTrigger
                            id="fiscal-year-select"
                            className="w-[200px]"
                        >
                            <SelectValue placeholder="Select fiscal year" />
                        </SelectTrigger>
                        <SelectContent>
                            {fiscalYears.map((year) => (
                                <SelectItem
                                    key={year.id}
                                    value={year.id.toString()}
                                >
                                    {year.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="flex flex-col gap-1.5">
                    <label
                        htmlFor="comparison-year-select"
                        className="text-sm font-medium text-muted-foreground"
                    >
                        Compare With
                    </label>
                    <Select
                        value={comparisonYearId?.toString() || 'none'}
                        onValueChange={(value) =>
                            onComparisonYearChange(
                                value === 'none' ? null : Number(value),
                            )
                        }
                    >
                        <SelectTrigger
                            id="comparison-year-select"
                            className="w-[200px]"
                        >
                            <SelectValue placeholder="None" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">None</SelectItem>
                            {fiscalYears
                                .filter((year) => year.id !== selectedYearId)
                                .map((year) => (
                                    <SelectItem
                                        key={year.id}
                                        value={year.id.toString()}
                                    >
                                        {year.name}
                                    </SelectItem>
                                ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>
        );
    },
);
