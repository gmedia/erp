import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/contexts/i18n-context';
import { formatCurrency } from '@/lib/utils';
import { formatNumberByRegionalSettings } from '@/utils/number-format';

type SummaryBucket = {
    name: string;
    quantity: string;
    value: string;
};

type InTransitSummary = {
    total_items: number;
    total_quantity: string;
    total_value: string;
};

type StockMonitorSummary = {
    total_items: number;
    total_quantity: string;
    total_stock_value: string;
    low_stock_items: number;
    by_warehouse: SummaryBucket[];
    by_category: SummaryBucket[];
    by_branch: SummaryBucket[];
    in_transit?: InTransitSummary;
};

type SummaryCardsProps = {
    summary?: StockMonitorSummary;
};

function formatNumber(value: string | number): string {
    return formatNumberByRegionalSettings(value);
}

function TopSummaryList({ items }: Readonly<{ items: SummaryBucket[] }>) {
    const { t } = useTranslation();
    if (items.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                {t('stock_monitor_summary.no_data')}
            </p>
        );
    }

    return (
        <div className="space-y-2">
            {items.slice(0, 5).map((item) => (
                <div
                    key={item.name}
                    className="flex items-center justify-between"
                >
                    <span className="text-sm">{item.name}</span>
                    <span className="text-sm font-medium">
                        {formatNumber(item.quantity)}
                    </span>
                </div>
            ))}
        </div>
    );
}

export function StockMonitorSummaryCards({
    summary,
}: Readonly<SummaryCardsProps>) {
    const { t } = useTranslation();
    return (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className="text-sm font-medium">
                        {t('stock_monitor_summary.total_sku_warehouse')}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">
                        {summary?.total_items ?? 0}
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className="text-sm font-medium">
                        {t('stock_monitor_summary.total_quantity')}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">
                        {formatNumber(summary?.total_quantity ?? 0)}
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className="text-sm font-medium">
                        {t('stock_monitor_summary.total_stock_value')}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">
                        {formatCurrency(summary?.total_stock_value ?? 0)}
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className="text-sm font-medium">
                        {t('stock_monitor_summary.low_stock_items')}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">
                        {summary?.low_stock_items ?? 0}
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className="text-sm font-medium">
                        {t('stock_monitor_summary.in_transit_value')}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">
                        {formatCurrency(summary?.in_transit?.total_value ?? 0)}
                    </div>
                    <p className="text-xs text-muted-foreground">
                        {t('stock_monitor_summary.in_transit_detail', {
                            items: String(
                                summary?.in_transit?.total_items ?? 0,
                            ),
                            quantity: formatNumber(
                                summary?.in_transit?.total_quantity ?? 0,
                            ),
                        })}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle className="text-sm font-medium">
                        {t('stock_monitor_summary.stock_by_warehouse')}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <TopSummaryList items={summary?.by_warehouse ?? []} />
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle className="text-sm font-medium">
                        {t('stock_monitor_summary.stock_by_category')}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <TopSummaryList items={summary?.by_category ?? []} />
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle className="text-sm font-medium">
                        {t('stock_monitor_summary.stock_by_branch')}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <TopSummaryList items={summary?.by_branch ?? []} />
                </CardContent>
            </Card>
        </div>
    );
}
