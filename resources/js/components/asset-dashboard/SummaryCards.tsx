import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency } from '@/lib/utils';
import { formatNumberByRegionalSettings } from '@/utils/number-format';
import { BarChart, Calculator, History, Package } from 'lucide-react';
import { AssetDashboardSummary } from '../../hooks/useAssetDashboard';

interface SummaryCardsProps {
    data?: AssetDashboardSummary;
    isLoading: boolean;
}

export function SummaryCards({ data, isLoading }: Readonly<SummaryCardsProps>) {
    if (isLoading || !data) {
        return (
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                {['summary-1', 'summary-2', 'summary-3', 'summary-4'].map(
                    (key) => (
                        <Card key={key} className="animate-pulse">
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="h-4 w-24 rounded bg-muted text-sm font-medium text-transparent"></CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="mt-1 h-8 w-24 rounded bg-muted text-2xl font-bold text-transparent"></div>
                            </CardContent>
                        </Card>
                    ),
                )}
            </div>
        );
    }

    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card className="overflow-hidden border-l-4 border-l-blue-500 transition-all hover:shadow-md">
                <CardHeader className="flex flex-row items-center justify-between pt-4 pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">
                        Total Assets
                    </CardTitle>
                    <Package className="h-4 w-4 text-blue-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-3xl font-bold">
                        {formatNumberByRegionalSettings(data.total_assets)}
                    </div>
                    <p className="mt-1 text-xs text-muted-foreground">
                        registered assets
                    </p>
                </CardContent>
            </Card>

            <Card className="overflow-hidden border-l-4 border-l-emerald-500 transition-all hover:shadow-md">
                <CardHeader className="flex flex-row items-center justify-between pt-4 pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">
                        Purchase Cost
                    </CardTitle>
                    <Calculator className="h-4 w-4 text-emerald-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-3xl font-bold">
                        {formatCurrency(data.total_purchase_cost)}
                    </div>
                    <p className="mt-1 text-xs text-muted-foreground">
                        total acquisition value
                    </p>
                </CardContent>
            </Card>

            <Card className="overflow-hidden border-l-4 border-l-indigo-500 transition-all hover:shadow-md">
                <CardHeader className="flex flex-row items-center justify-between pt-4 pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">
                        Book Value
                    </CardTitle>
                    <BarChart className="h-4 w-4 text-indigo-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-3xl font-bold">
                        {formatCurrency(data.total_book_value)}
                    </div>
                    <p className="mt-1 text-xs text-muted-foreground">
                        current estimated value
                    </p>
                </CardContent>
            </Card>

            <Card className="overflow-hidden border-l-4 border-l-rose-500 transition-all hover:shadow-md">
                <CardHeader className="flex flex-row items-center justify-between pt-4 pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">
                        Accum. Depreciation
                    </CardTitle>
                    <History className="h-4 w-4 text-rose-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-3xl font-bold">
                        {formatCurrency(data.total_accumulated_depreciation)}
                    </div>
                    <p className="mt-1 text-xs text-muted-foreground">
                        total value deprecated
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
