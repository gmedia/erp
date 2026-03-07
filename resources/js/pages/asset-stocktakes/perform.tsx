import { Helmet } from 'react-helmet-async';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { StocktakeItemManager } from '@/components/asset-stocktakes/StocktakeItemManager';
import { useStocktakeItems } from '@/hooks/asset-stocktakes/useStocktakeItems';
import { useEffect } from 'react';
import { Loader2 } from 'lucide-react';
import { useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import axios from '@/lib/axios';

export default function PerformAssetStocktakePage() {
    const { id } = useParams<{ id: string }>();
    const { data: stocktake, isLoading: isStocktakeLoading } = useQuery({
        queryKey: ['asset-stocktakes', id],
        queryFn: async () => {
            const { data } = await axios.get(`/api/asset-stocktakes/${id}`);
            return data.data;
        },
    });

    const { loading, items, fetchItems, saveItems } = useStocktakeItems();

    useEffect(() => {
        if (stocktake?.ulid) {
            fetchItems(stocktake.ulid);
        }
    }, [stocktake?.ulid, fetchItems]);

    const breadcrumbs = [
        { title: 'Home', href: '/' },
        { title: 'Asset Stocktakes', href: '/asset-stocktakes' },
        { title: stocktake ? `Perform: ${stocktake.reference}` : 'Perform Stocktake', href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Helmet><title>{stocktake ? `Perform Stocktake ${stocktake.reference}` : 'Perform Stocktake'}</title></Helmet>
            
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Perform Asset Stocktake</CardTitle>
                        {isStocktakeLoading ? (
                            <div className="h-5 w-48 animate-pulse rounded bg-muted"></div>
                        ) : stocktake && (
                            <CardDescription>
                                Branch: <strong>{stocktake.branch?.name}</strong> | Ref: <strong>{stocktake.reference}</strong>
                            </CardDescription>
                        )}
                    </CardHeader>
                    <CardContent>
                        {isStocktakeLoading || (loading && items.length === 0) ? (
                            <div className="flex h-32 items-center justify-center">
                                <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                            </div>
                        ) : stocktake ? (
                            <StocktakeItemManager
                                stocktakeBranchId={stocktake.branch_id}
                                items={items}
                                loading={loading}
                                onSave={(data) => saveItems(stocktake.ulid, data)}
                            />
                        ) : (
                             <div className="text-center text-muted-foreground">Stocktake not found</div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
