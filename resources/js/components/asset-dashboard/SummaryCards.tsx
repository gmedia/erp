import { KpiCard } from '@/components/common/KpiCard';
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
            <KpiCard
                title="Total Assets"
                icon={Package}
                iconColor="text-info"
                formattedValue={formatNumberByRegionalSettings(
                    data.total_assets,
                )}
            >
                <p className="mt-1 text-xs text-muted-foreground">
                    registered assets
                </p>
            </KpiCard>

            <KpiCard
                title="Purchase Cost"
                icon={Calculator}
                iconColor="text-success"
                formattedValue={formatCurrency(data.total_purchase_cost, 0)}
            >
                <p className="mt-1 text-xs text-muted-foreground">
                    total acquisition value
                </p>
            </KpiCard>

            <KpiCard
                title="Book Value"
                icon={BarChart}
                iconColor="text-chart-3"
                formattedValue={formatCurrency(data.total_book_value, 0)}
            >
                <p className="mt-1 text-xs text-muted-foreground">
                    current estimated value
                </p>
            </KpiCard>

            <KpiCard
                title="Accum. Depreciation"
                icon={History}
                iconColor="text-destructive-foreground"
                formattedValue={formatCurrency(
                    data.total_accumulated_depreciation,
                    0,
                )}
            >
                <p className="mt-1 text-xs text-muted-foreground">
                    total value depreciated
                </p>
            </KpiCard>
        </div>
    );
}
