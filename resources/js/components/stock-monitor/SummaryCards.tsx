import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type SummaryBucket = {
    name: string;
    quantity: string;
    value: string;
};

type StockMonitorSummary = {
    total_items: number;
    total_quantity: string;
    total_stock_value: string;
    low_stock_items: number;
    by_warehouse: SummaryBucket[];
    by_category: SummaryBucket[];
    by_branch: SummaryBucket[];
};

type SummaryCardsProps = {
    summary?: StockMonitorSummary;
};

function formatNumber(value: string | number): string {
    return new Intl.NumberFormat().format(Number(value));
}

function TopSummaryList({ items }: { items: SummaryBucket[] }) {
    if (items.length === 0) {
        return <p className="text-sm text-muted-foreground">No data</p>;
    }

    return (
        <div className="space-y-2">
            {items.slice(0, 5).map((item) => (
                <div key={item.name} className="flex items-center justify-between">
                    <span className="text-sm">{item.name}</span>
                    <span className="text-sm font-medium">
                        {formatNumber(item.quantity)}
                    </span>
                </div>
            ))}
        </div>
    );
}

export function StockMonitorSummaryCards({ summary }: SummaryCardsProps) {
    return (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className="text-sm font-medium">
                        Total SKU-Warehouse
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
                        Total Quantity
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
                        Total Stock Value
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">
                        {formatNumber(summary?.total_stock_value ?? 0)}
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className="text-sm font-medium">
                        Low Stock Items
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">
                        {summary?.low_stock_items ?? 0}
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle className="text-sm font-medium">
                        Stock by Warehouse
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <TopSummaryList items={summary?.by_warehouse ?? []} />
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle className="text-sm font-medium">
                        Stock by Category
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <TopSummaryList items={summary?.by_category ?? []} />
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle className="text-sm font-medium">
                        Stock by Branch
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <TopSummaryList items={summary?.by_branch ?? []} />
                </CardContent>
            </Card>
        </div>
    );
}
