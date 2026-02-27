import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { AssetDashboardSummary } from '../../hooks/useAssetDashboard';
import { formatCurrency } from '@/lib/utils';
import { Package, Calculator, History, BarChart } from 'lucide-react';

interface SummaryCardsProps {
    data?: AssetDashboardSummary;
    isLoading: boolean;
}

export function SummaryCards({ data, isLoading }: SummaryCardsProps) {
    if (isLoading || !data) {
        return (
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                {[...Array(4)].map((_, i) => (
                    <Card key={i} className="animate-pulse">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-transparent bg-muted rounded h-4 w-24"></CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-transparent bg-muted rounded w-24 h-8 mt-1"></div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        );
    }

    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card className="overflow-hidden transition-all hover:shadow-md border-l-4 border-l-blue-500">
                <CardHeader className="flex flex-row items-center justify-between pb-2 pt-4">
                    <CardTitle className="text-sm font-medium text-muted-foreground">
                        Total Assets
                    </CardTitle>
                    <Package className="h-4 w-4 text-blue-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-3xl font-bold">{data.total_assets.toLocaleString()}</div>
                    <p className="text-xs text-muted-foreground mt-1">registered assets</p>
                </CardContent>
            </Card>

            <Card className="overflow-hidden transition-all hover:shadow-md border-l-4 border-l-emerald-500">
                <CardHeader className="flex flex-row items-center justify-between pb-2 pt-4">
                    <CardTitle className="text-sm font-medium text-muted-foreground">
                        Purchase Cost
                    </CardTitle>
                    <Calculator className="h-4 w-4 text-emerald-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-3xl font-bold">{formatCurrency(data.total_purchase_cost)}</div>
                    <p className="text-xs text-muted-foreground mt-1">total acquisition value</p>
                </CardContent>
            </Card>

            <Card className="overflow-hidden transition-all hover:shadow-md border-l-4 border-l-indigo-500">
                <CardHeader className="flex flex-row items-center justify-between pb-2 pt-4">
                    <CardTitle className="text-sm font-medium text-muted-foreground">
                        Book Value
                    </CardTitle>
                    <BarChart className="h-4 w-4 text-indigo-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-3xl font-bold">{formatCurrency(data.total_book_value)}</div>
                    <p className="text-xs text-muted-foreground mt-1">current estimated value</p>
                </CardContent>
            </Card>

            <Card className="overflow-hidden transition-all hover:shadow-md border-l-4 border-l-rose-500">
                <CardHeader className="flex flex-row items-center justify-between pb-2 pt-4">
                    <CardTitle className="text-sm font-medium text-muted-foreground">
                        Accum. Depreciation
                    </CardTitle>
                    <History className="h-4 w-4 text-rose-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-3xl font-bold">{formatCurrency(data.total_accumulated_depreciation)}</div>
                    <p className="text-xs text-muted-foreground mt-1">total value deprecated</p>
                </CardContent>
            </Card>
        </div>
    );
}
