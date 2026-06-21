import { Button } from '@/components/ui/button';
import { Download, Loader2 } from 'lucide-react';

type FinancialReportExportButtonProps = {
    exporting: boolean;
    selectedYearId: number;
    comparisonYearId?: number;
    branchId?: string | null;
    onExport: (payload: Record<string, string>) => void;
};

export function FinancialReportExportButton({
    exporting,
    selectedYearId,
    comparisonYearId,
    branchId,
    onExport,
}: FinancialReportExportButtonProps) {
    return (
        <Button
            variant="outline"
            size="sm"
            disabled={!selectedYearId || exporting}
            onClick={() =>
                onExport({
                    fiscal_year_id: String(selectedYearId),
                    ...(comparisonYearId && {
                        comparison_year_id: String(comparisonYearId),
                    }),
                    ...(branchId && { branch_id: branchId }),
                })
            }
        >
            {exporting ? (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
            ) : (
                <Download className="mr-2 h-4 w-4" />
            )}
            {exporting ? 'Exporting...' : 'Export'}
        </Button>
    );
}
