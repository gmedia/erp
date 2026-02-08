'use client';

import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { type Asset } from '@/types/asset';
import { Head } from '@inertiajs/react';
import { format } from 'date-fns';
import {
    Activity,
    Calendar,
    ClipboardCheck,
    History,
    Info,
    TrendingDown,
    Wrench,
} from 'lucide-react';

interface Props {
    asset: {
        data: Asset & {
            movements?: any[];
            maintenances?: any[];
            stocktake_items?: any[];
            depreciation_lines?: any[];
        };
    };
}

export default function AssetProfile({ asset }: Props) {
    const item = asset.data;

    const formatDate = (dateString: string | null) => {
        if (!dateString) return 'N/A';
        try {
            return format(new Date(dateString), 'PPP');
        } catch (e) {
            return dateString;
        }
    };

    const formatCurrency = (value: string | number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: item.currency || 'IDR',
            minimumFractionDigits: 0,
        }).format(Number(value));
    };

    const getStatusVariant = (status: string) => {
        switch (status) {
            case 'active':
                return 'default';
            case 'maintenance':
                return 'secondary';
            case 'disposed':
                return 'destructive';
            case 'lost':
                return 'destructive';
            default:
                return 'outline';
        }
    };

    const getConditionVariant = (condition: string) => {
        switch (condition) {
            case 'good':
                return 'default';
            case 'needs_repair':
                return 'secondary';
            case 'damaged':
                return 'destructive';
            default:
                return 'outline';
        }
    };

    const breadcrumbs = [
        { title: 'Assets', href: '/assets' },
        { title: item.asset_code, href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Asset Profile - ${item.asset_code}`} />

            <div className="flex flex-col gap-6 p-6">
                {/* Header Section */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2">
                            <h1 className="text-3xl font-bold tracking-tight">{item.name}</h1>
                            <Badge variant="outline" className="text-lg py-1 px-3">
                                {item.asset_code}
                            </Badge>
                        </div>
                        <p className="text-muted-foreground">
                            {item.category?.name} &bull; {item.model?.model_name || 'Generic Model'}
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Badge variant={getStatusVariant(item.status)} className="text-sm px-4 py-1 capitalize">
                            {item.status}
                        </Badge>
                        <Badge variant={getConditionVariant(item.condition || '')} className="text-sm px-4 py-1 capitalize">
                            {item.condition?.replace('_', ' ') || 'Unknown'}
                        </Badge>
                    </div>
                </div>

                <Separator />

                <Tabs defaultValue="summary" className="w-full">
                    <TabsList className="grid w-full grid-cols-2 md:grid-cols-5 h-auto">
                        <TabsTrigger value="summary" className="py-2">
                            <Info className="mr-2 h-4 w-4" />
                            Summary
                        </TabsTrigger>
                        <TabsTrigger value="movements" className="py-2">
                            <History className="mr-2 h-4 w-4" />
                            Movements
                        </TabsTrigger>
                        <TabsTrigger value="maintenance" className="py-2">
                            <Wrench className="mr-2 h-4 w-4" />
                            Maintenance
                        </TabsTrigger>
                        <TabsTrigger value="stocktake" className="py-2">
                            <ClipboardCheck className="mr-2 h-4 w-4" />
                            Stocktake
                        </TabsTrigger>
                        <TabsTrigger value="depreciation" className="py-2">
                            <TrendingDown className="mr-2 h-4 w-4" />
                            Depreciation
                        </TabsTrigger>
                    </TabsList>

                    {/* Summary Tab */}
                    <TabsContent value="summary" className="space-y-6 mt-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {/* General Information */}
                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-sm font-medium flex items-center">
                                        <Info className="mr-2 h-4 w-4 text-primary" />
                                        General Information
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Serial Number</span>
                                        <span className="font-medium">{item.serial_number || '-'}</span>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Barcode</span>
                                        <span className="font-medium">{item.barcode || '-'}</span>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Purchase Date</span>
                                        <span className="font-medium">{formatDate(item.purchase_date)}</span>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Warranty Until</span>
                                        <span className="font-medium">{formatDate(item.warranty_end_date)}</span>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Location & Assignment */}
                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-sm font-medium flex items-center">
                                        <Activity className="mr-2 h-4 w-4 text-primary" />
                                        Current Location & PIC
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Branch</span>
                                        <span className="font-medium">{item.branch?.name || '-'}</span>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Location</span>
                                        <span className="font-medium">{item.location?.name || '-'}</span>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Department</span>
                                        <span className="font-medium">{item.department?.name || '-'}</span>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Person in Charge</span>
                                        <span className="font-medium text-primary">{item.employee?.name || 'Unassigned'}</span>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Financial Summary */}
                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-sm font-medium flex items-center">
                                        <TrendingDown className="mr-2 h-4 w-4 text-primary" />
                                        Financial Summary
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Purchase Cost</span>
                                        <span className="font-medium">{formatCurrency(item.purchase_cost)}</span>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Useful Life</span>
                                        <span className="font-medium">{item.useful_life_months} Months</span>
                                    </div>
                                    <div className="flex justify-between text-sm text-primary font-bold pt-2 border-t border-dashed">
                                        <span>Current Book Value</span>
                                        <span>{formatCurrency(item.book_value)}</span>
                                    </div>
                                    <div className="flex justify-between text-xs text-muted-foreground pt-1">
                                        <span>Accumulated Depr.</span>
                                        <span>{formatCurrency(item.accumulated_depreciation)}</span>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {item.notes && (
                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-sm font-medium">Notes</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm text-muted-foreground whitespace-pre-wrap">{item.notes}</p>
                                </CardContent>
                            </Card>
                        )}
                    </TabsContent>

                    {/* Movements Tab */}
                    <TabsContent value="movements" className="mt-6">
                        <Card>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Type</TableHead>
                                            <TableHead>Date</TableHead>
                                            <TableHead>Origin</TableHead>
                                            <TableHead>Destination</TableHead>
                                            <TableHead>Ref/Notes</TableHead>
                                            <TableHead>PIC</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {item.movements?.length ? (
                                            item.movements.map((m) => (
                                                <TableRow key={m.id}>
                                                    <TableCell className="capitalize font-medium">{m.movement_type}</TableCell>
                                                    <TableCell className="whitespace-nowrap">{formatDate(m.moved_at)}</TableCell>
                                                    <TableCell className="text-xs">
                                                        {m.from_branch && <div>{m.from_branch}</div>}
                                                        {m.from_location && <div className="text-muted-foreground">{m.from_location}</div>}
                                                        {m.from_employee && <div className="text-primary">{m.from_employee}</div>}
                                                    </TableCell>
                                                    <TableCell className="text-xs">
                                                        {m.to_branch && <div>{m.to_branch}</div>}
                                                        {m.to_location && <div className="text-muted-foreground">{m.to_location}</div>}
                                                        {m.to_employee && <div className="text-primary">{m.to_employee}</div>}
                                                    </TableCell>
                                                    <TableCell className="max-w-[200px]">
                                                        <div className="text-xs font-semibold">{m.reference}</div>
                                                        <div className="text-xs text-muted-foreground truncate">{m.notes}</div>
                                                    </TableCell>
                                                    <TableCell className="text-xs whitespace-nowrap">{m.created_by}</TableCell>
                                                </TableRow>
                                            ))
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={6} className="text-center py-10 text-muted-foreground">
                                                    No movement history found.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Maintenance Tab */}
                    <TabsContent value="maintenance" className="mt-6">
                        <Card>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Type</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Date</TableHead>
                                            <TableHead>Supplier</TableHead>
                                            <TableHead className="text-right">Cost</TableHead>
                                            <TableHead>Notes</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {item.maintenances?.length ? (
                                            item.maintenances.map((m) => (
                                                <TableRow key={m.id}>
                                                    <TableCell className="capitalize font-medium">{m.maintenance_type}</TableCell>
                                                    <TableCell>
                                                        <Badge variant={m.status === 'completed' ? 'default' : 'outline'} className="capitalize">
                                                            {m.status}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="text-xs whitespace-nowrap">
                                                        <div>S: {formatDate(m.scheduled_at)}</div>
                                                        <div className="text-muted-foreground">P: {formatDate(m.performed_at)}</div>
                                                    </TableCell>
                                                    <TableCell className="text-xs">{m.supplier || '-'}</TableCell>
                                                    <TableCell className="text-right">{formatCurrency(m.cost)}</TableCell>
                                                    <TableCell className="max-w-[200px] text-xs text-muted-foreground truncate">
                                                        {m.notes}
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={6} className="text-center py-10 text-muted-foreground">
                                                    No maintenance history found.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Stocktake Tab */}
                    <TabsContent value="stocktake" className="mt-6">
                        <Card>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Reference</TableHead>
                                            <TableHead>Date</TableHead>
                                            <TableHead>Branch</TableHead>
                                            <TableHead>Expect/Found</TableHead>
                                            <TableHead>Result</TableHead>
                                            <TableHead>Notes</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {item.stocktake_items?.length ? (
                                            item.stocktake_items.map((s) => (
                                                <TableRow key={s.id}>
                                                    <TableCell className="font-medium">{s.stocktake_reference}</TableCell>
                                                    <TableCell className="whitespace-nowrap">{s.stocktake_date}</TableCell>
                                                    <TableCell>{s.branch}</TableCell>
                                                    <TableCell className="text-xs">
                                                        <div className="text-muted-foreground">E: {s.expected_location}</div>
                                                        <div className="font-medium text-primary">F: {s.found_location}</div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant={s.result === 'found' ? 'default' : 'destructive'} className="capitalize">
                                                            {s.result}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="max-w-[200px] text-xs text-muted-foreground truncate">
                                                        {s.notes}
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={6} className="text-center py-10 text-muted-foreground">
                                                    No stocktake history found.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Depreciation Tab */}
                    <TabsContent value="depreciation" className="mt-6">
                        <Card>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Period</TableHead>
                                            <TableHead>FY</TableHead>
                                            <TableHead className="text-right">Amount</TableHead>
                                            <TableHead className="text-right">Accum. (After)</TableHead>
                                            <TableHead className="text-right">Book Value</TableHead>
                                            <TableHead>Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {item.depreciation_lines?.length ? (
                                            item.depreciation_lines.map((d) => (
                                                <TableRow key={d.id}>
                                                    <TableCell className="font-medium text-xs">{d.period}</TableCell>
                                                    <TableCell>{d.fiscal_year}</TableCell>
                                                    <TableCell className="text-right">{formatCurrency(d.amount)}</TableCell>
                                                    <TableCell className="text-right text-xs text-muted-foreground">{formatCurrency(d.accumulated_after)}</TableCell>
                                                    <TableCell className="text-right font-semibold text-primary">{formatCurrency(d.book_value_after)}</TableCell>
                                                    <TableCell>
                                                        <Badge variant={d.status === 'posted' ? 'default' : 'outline'} className="capitalize text-[10px]">
                                                            {d.status}
                                                        </Badge>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={6} className="text-center py-10 text-muted-foreground">
                                                    No depreciation history found.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
