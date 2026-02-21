import { Head } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { StocktakeItemManager } from '@/components/asset-stocktakes/StocktakeItemManager';
import { useStocktakeItems } from '@/hooks/asset-stocktakes/useStocktakeItems';
import { useEffect } from 'react';
import { Loader2 } from 'lucide-react';

interface PerformPageProps {
    stocktake: {
        id: number;
        ulid: string;
        branch_id: number;
        reference: string;
        status: string;
        branch?: {
            name: string;
        };
    };
}

export default function PerformAssetStocktakePage({ stocktake }: PerformPageProps) {
    const { loading, items, fetchItems, saveItems } = useStocktakeItems();

    const breadcrumbs = [
        { title: 'Home', href: '/' },
        { title: 'Asset Stocktakes', href: '/asset-stocktakes' },
        { title: `Perform: ${stocktake.reference}`, href: '#' },
    ];

    useEffect(() => {
        fetchItems(stocktake.ulid);
    }, [stocktake.ulid, fetchItems]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Perform Stocktake ${stocktake.reference}`} />
            
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Perform Asset Stocktake</CardTitle>
                        <CardDescription>
                            Branch: <strong>{stocktake.branch?.name}</strong> | Ref: <strong>{stocktake.reference}</strong>
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {loading && items.length === 0 ? (
                            <div className="flex h-32 items-center justify-center">
                                <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                            </div>
                        ) : (
                            <StocktakeItemManager
                                stocktakeBranchId={stocktake.branch_id}
                                items={items}
                                loading={loading}
                                onSave={(data) => saveItems(stocktake.ulid, data)}
                            />
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
