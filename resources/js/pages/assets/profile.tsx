'use client';

import { ApprovalHistoryTimeline } from '@/components/approvals/ApprovalHistoryTimeline';
import { EntityStateActions } from '@/components/pipeline/EntityStateActions';
import { EntityStateTimeline } from '@/components/pipeline/EntityStateTimeline';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import {
    Asset,
    AssetDepreciationLine,
    AssetMovement,
    AssetStocktakeItem,
} from '@/types/asset';
import { type AssetMaintenance } from '@/types/asset-maintenance';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { format } from 'date-fns';
import {
    Activity,
    AlertCircle,
    Barcode,
    Building2,
    Calendar,
    CalendarDays,
    CircleDollarSign,
    ClipboardCheck,
    Clock,
    Hash,
    History,
    Info,
    Layers,
    Loader2,
    MapPin,
    Package,
    Printer,
    Settings,
    ShieldCheck,
    TrendingDown,
    User,
    Wrench,
} from 'lucide-react';
import { QRCodeSVG } from 'qrcode.react';
import { useCallback, useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { useParams } from 'react-router-dom';

export default function AssetProfile() {
    const { id } = useParams<{ id: string }>();
    const [timelineKey, setTimelineKey] = useState(Date.now());
    const queryClient = useQueryClient();

    const {
        data: assetData,
        isLoading,
        error,
    } = useQuery({
        queryKey: ['asset-profile', id],
        queryFn: async () => {
            const res = await axios.get(`/api/assets/${id}/profile`);
            return res.data;
        },
        enabled: !!id,
    });

    const item: Asset = assetData?.asset?.data;

    const handleStateChange = useCallback(() => {
        setTimelineKey(Date.now());
        queryClient.invalidateQueries({ queryKey: ['asset-profile', id] });
    }, [queryClient, id]);

    const formatDate = (dateString: string | null) => {
        if (!dateString) return 'N/A';
        try {
            return format(new Date(dateString), 'PPP');
        } catch {
            return dateString;
        }
    };

    const formatCurrency = (value: string | number) => {
        return formatCurrencyByRegionalSettings(value, {
            locale: 'id-ID',
            currency: item?.currency || 'IDR',
            minimumFractionDigits: 0,
        });
    };

    if (isLoading || !item) {
        return (
            <AppLayout
                breadcrumbs={[
                    { title: 'Assets', href: '/assets' },
                    { title: 'Loading...', href: '#' },
                ]}
            >
                <div className="flex min-h-[50vh] items-center justify-center">
                    <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                </div>
            </AppLayout>
        );
    }

    if (error) {
        return (
            <AppLayout
                breadcrumbs={[
                    { title: 'Assets', href: '/assets' },
                    { title: 'Error', href: '#' },
                ]}
            >
                <div className="flex min-h-[50vh] items-center justify-center">
                    <p className="text-destructive">
                        Failed to load asset profile.
                    </p>
                </div>
            </AppLayout>
        );
    }

    const getDepreciationProgress = () => {
        const purchaseCost = Number(item.purchase_cost) || 0;
        const accumulatedDep = Number(item.accumulated_depreciation) || 0;
        if (purchaseCost === 0) return 0;
        return Math.min((accumulatedDep / purchaseCost) * 100, 100);
    };

    const handlePrint = () => {
        const printWindow = window.open('', '_blank');
        if (!printWindow) return;

        const qrSvg = document.querySelector('.qr-code-svg');
        const qrSvgHtml = qrSvg ? qrSvg.outerHTML : '';
        const printDocument = printWindow.document;

        printDocument.title = `Print QR Code - ${item.asset_code}`;

        const style = printDocument.createElement('style');
        style.textContent = `
            body {
                font-family: system-ui, -apple-system, sans-serif;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 100vh;
                margin: 0;
                text-align: center;
            }
            .container {
                border: 2px solid #eee;
                padding: 40px;
                border-radius: 20px;
            }
            .qr-wrapper svg {
                width: 250px;
                height: 250px;
            }
            .info {
                margin-top: 20px;
            }
            .code {
                font-size: 24px;
                font-weight: bold;
                font-family: monospace;
                margin: 10px 0;
            }
            .name {
                font-size: 18px;
                color: #666;
            }
            @media print {
                body { height: auto; }
                .container { border: none; }
            }
        `;

        printDocument.head.innerHTML = '';
        printDocument.head.append(style);
        printDocument.body.innerHTML = `
            <div class="container">
                <div class="qr-wrapper">${qrSvgHtml}</div>
                <div class="info">
                    <div class="code">${item.asset_code}</div>
                    <div class="name">${item.name}</div>
                </div>
            </div>
        `;

        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 500);
    };

    const breadcrumbs = [
        { title: 'Assets', href: '/assets' },
        { title: item.asset_code, href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Helmet>
                <title>{`Asset Profile - ${item.asset_code}`}</title>
            </Helmet>

            <div className="flex flex-col gap-6 p-6">
                {/* Header Section - Enhanced */}
                <div className="relative overflow-hidden rounded-xl border bg-gradient-to-r from-primary/5 via-primary/10 to-transparent p-6">
                    <div className="absolute top-0 right-0 -z-10 h-64 w-64 opacity-20">
                        <Package className="h-full w-full text-primary/30" />
                    </div>
                    <div className="flex flex-col justify-between gap-6 md:flex-row md:items-start">
                        <div className="flex items-start gap-4">
                            {/* Icon Box */}
                            <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary shadow-sm">
                                <Layers className="h-8 w-8" />
                            </div>
                            <div className="space-y-2">
                                <div className="flex flex-wrap items-center gap-2">
                                    <h1 className="text-2xl font-bold tracking-tight md:text-3xl">
                                        {item.name}
                                    </h1>
                                    <Badge
                                        variant="outline"
                                        className="px-3 py-1 font-mono text-sm"
                                    >
                                        {item.asset_code}
                                    </Badge>
                                </div>
                                <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                                    <span className="flex items-center gap-1">
                                        <Package className="h-4 w-4" />
                                        {item.category?.name || 'Uncategorized'}
                                    </span>
                                    <span className="hidden sm:inline">•</span>
                                    <span className="flex items-center gap-1">
                                        <Settings className="h-4 w-4" />
                                        {item.model?.model_name ||
                                            'Generic Model'}
                                    </span>
                                    {item.model?.manufacturer && (
                                        <>
                                            <span className="hidden sm:inline">
                                                •
                                            </span>
                                            <span>
                                                {item.model.manufacturer}
                                            </span>
                                        </>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-4">
                            {item.qrcode_url && (
                                <div className="flex flex-col items-center gap-2">
                                    <div className="rounded-lg border border-primary/10 bg-white p-2 shadow-sm">
                                        <QRCodeSVG
                                            value={item.qrcode_url || ''}
                                            size={80}
                                            level="H"
                                            includeMargin={false}
                                            className="qr-code-svg h-20 w-20"
                                        />
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="h-8 gap-2 text-xs text-primary hover:bg-primary/5 hover:text-primary"
                                        onClick={handlePrint}
                                    >
                                        <Printer className="h-3.5 w-3.5" />
                                        Print QR
                                    </Button>
                                </div>
                            )}
                            <div className="flex flex-col gap-2">
                                <EntityStateActions
                                    entityType="asset"
                                    entityId={item.ulid}
                                    onStateChange={handleStateChange}
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <Tabs defaultValue="summary" className="w-full">
                    <TabsList className="mb-4 grid !h-auto w-full grid-cols-2 gap-2 bg-muted/50 p-1 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7">
                        <TabsTrigger
                            value="summary"
                            className="data-[state=active]:bg-background data-[state=active]:shadow-sm"
                        >
                            <Info className="mr-2 h-4 w-4" />
                            Summary
                        </TabsTrigger>
                        <TabsTrigger
                            value="movements"
                            className="data-[state=active]:bg-background data-[state=active]:shadow-sm"
                        >
                            <History className="mr-2 h-4 w-4" />
                            Movements
                        </TabsTrigger>
                        <TabsTrigger
                            value="maintenance"
                            className="data-[state=active]:bg-background data-[state=active]:shadow-sm"
                        >
                            <Wrench className="mr-2 h-4 w-4" />
                            Maintenance
                        </TabsTrigger>
                        <TabsTrigger
                            value="stocktake"
                            className="data-[state=active]:bg-background data-[state=active]:shadow-sm"
                        >
                            <ClipboardCheck className="mr-2 h-4 w-4" />
                            Stocktake
                        </TabsTrigger>
                        <TabsTrigger
                            value="depreciation"
                            className="data-[state=active]:bg-background data-[state=active]:shadow-sm"
                        >
                            <TrendingDown className="mr-2 h-4 w-4" />
                            Depreciation
                        </TabsTrigger>
                        <TabsTrigger
                            value="timeline"
                            className="data-[state=active]:bg-background data-[state=active]:shadow-sm"
                        >
                            <History className="mr-2 h-4 w-4" />
                            Timeline
                        </TabsTrigger>
                        <TabsTrigger
                            value="approvals"
                            className="data-[state=active]:bg-background data-[state=active]:shadow-sm"
                        >
                            <History className="mr-2 h-4 w-4" />
                            Approvals
                        </TabsTrigger>
                    </TabsList>

                    {/* Summary Tab */}
                    <TabsContent value="summary" className="mt-6 space-y-6">
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {/* General Information */}
                            <Card
                                className="group transition-all duration-200 hover:border-primary/30 hover:shadow-md"
                                data-testid="summary-general-info"
                            >
                                <CardHeader className="pb-3">
                                    <CardTitle className="flex items-center gap-2 text-sm font-medium">
                                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                            <Info className="h-4 w-4" />
                                        </div>
                                        <span>General Information</span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="flex items-center gap-2 text-muted-foreground">
                                            <Hash className="h-3.5 w-3.5" />
                                            Serial Number
                                        </span>
                                        <span className="font-mono font-medium">
                                            {item.serial_number || '-'}
                                        </span>
                                    </div>
                                    <Separator />
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="flex items-center gap-2 text-muted-foreground">
                                            <Barcode className="h-3.5 w-3.5" />
                                            Barcode
                                        </span>
                                        <span className="font-mono font-medium">
                                            {item.barcode || '-'}
                                        </span>
                                    </div>
                                    <Separator />
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="flex items-center gap-2 text-muted-foreground">
                                            <CalendarDays className="h-3.5 w-3.5" />
                                            Purchase Date
                                        </span>
                                        <span className="font-medium">
                                            {formatDate(item.purchase_date)}
                                        </span>
                                    </div>
                                    <Separator />
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="flex items-center gap-2 text-muted-foreground">
                                            <ShieldCheck className="h-3.5 w-3.5" />
                                            Warranty Until
                                        </span>
                                        <span className="font-medium">
                                            {formatDate(item.warranty_end_date)}
                                        </span>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Location & Assignment */}
                            <Card
                                className="group transition-all duration-200 hover:border-primary/30 hover:shadow-md"
                                data-testid="summary-location-info"
                            >
                                <CardHeader className="pb-3">
                                    <CardTitle className="flex items-center gap-2 text-sm font-medium">
                                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                                            <MapPin className="h-4 w-4" />
                                        </div>
                                        <span>Current Location & PIC</span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="flex items-center gap-2 text-muted-foreground">
                                            <Building2 className="h-3.5 w-3.5" />
                                            Branch
                                        </span>
                                        <span
                                            className="font-medium"
                                            data-testid="asset-branch"
                                        >
                                            {item.branch?.name || '-'}
                                        </span>
                                    </div>
                                    <Separator />
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="flex items-center gap-2 text-muted-foreground">
                                            <MapPin className="h-3.5 w-3.5" />
                                            Location
                                        </span>
                                        <span
                                            className="font-medium"
                                            data-testid="asset-location"
                                        >
                                            {item.location?.name || '-'}
                                        </span>
                                    </div>
                                    <Separator />
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="flex items-center gap-2 text-muted-foreground">
                                            <Activity className="h-3.5 w-3.5" />
                                            Department
                                        </span>
                                        <span
                                            className="font-medium"
                                            data-testid="asset-department"
                                        >
                                            {item.department?.name || '-'}
                                        </span>
                                    </div>
                                    <Separator />
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="flex items-center gap-2 text-muted-foreground">
                                            <User className="h-3.5 w-3.5" />
                                            Person in Charge
                                        </span>
                                        <Badge
                                            variant="outline"
                                            className="font-medium"
                                            data-testid="asset-employee"
                                        >
                                            {item.employee?.name ||
                                                'Unassigned'}
                                        </Badge>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Financial Summary - Enhanced */}
                            <Card
                                className="group transition-all duration-200 hover:border-primary/30 hover:shadow-md"
                                data-testid="summary-financial-info"
                            >
                                <CardHeader className="pb-3">
                                    <CardTitle className="flex items-center gap-2 text-sm font-medium">
                                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                                            <CircleDollarSign className="h-4 w-4" />
                                        </div>
                                        <span>Financial Summary</span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="text-muted-foreground">
                                            Purchase Cost
                                        </span>
                                        <span className="font-semibold">
                                            {formatCurrency(item.purchase_cost)}
                                        </span>
                                    </div>
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="text-muted-foreground">
                                            Useful Life
                                        </span>
                                        <span className="font-medium">
                                            {item.useful_life_months} Months
                                        </span>
                                    </div>
                                    <Separator />
                                    {/* Depreciation Progress */}
                                    <div className="space-y-2">
                                        <div className="flex items-center justify-between text-xs">
                                            <span className="text-muted-foreground">
                                                Depreciation Progress
                                            </span>
                                            <span className="font-medium">
                                                {getDepreciationProgress().toFixed(
                                                    1,
                                                )}
                                                %
                                            </span>
                                        </div>
                                        <Progress
                                            value={getDepreciationProgress()}
                                            className="h-2"
                                        />
                                        <div className="flex items-center justify-between text-xs text-muted-foreground">
                                            <span>
                                                Accumulated:{' '}
                                                {formatCurrency(
                                                    item.accumulated_depreciation,
                                                )}
                                            </span>
                                        </div>
                                    </div>
                                    <Separator />
                                    {/* Book Value Highlight */}
                                    <div className="rounded-lg bg-primary/5 p-3">
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm font-medium text-muted-foreground">
                                                Current Book Value
                                            </span>
                                            <span className="text-lg font-bold text-primary">
                                                {formatCurrency(
                                                    item.book_value,
                                                )}
                                            </span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {item.notes && (
                            <Card className="transition-all duration-200 hover:shadow-md">
                                <CardHeader className="pb-3">
                                    <CardTitle className="flex items-center gap-2 text-sm font-medium">
                                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                            <Info className="h-4 w-4" />
                                        </div>
                                        <span>Notes</span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm leading-relaxed whitespace-pre-wrap text-muted-foreground">
                                        {item.notes}
                                    </p>
                                </CardContent>
                            </Card>
                        )}
                    </TabsContent>

                    {/* Movements Tab */}
                    <TabsContent value="movements" className="mt-6">
                        <Card className="overflow-hidden">
                            <CardContent className="p-0">
                                {item.movements?.length ? (
                                    <Table>
                                        <TableHeader>
                                            <TableRow className="bg-muted/50">
                                                <TableHead>Type</TableHead>
                                                <TableHead>Date</TableHead>
                                                <TableHead>Origin</TableHead>
                                                <TableHead>
                                                    Destination
                                                </TableHead>
                                                <TableHead>Ref/Notes</TableHead>
                                                <TableHead>PIC</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {item.movements?.map(
                                                (m: AssetMovement) => (
                                                    <TableRow
                                                        key={m.id}
                                                        className="hover:bg-muted/30"
                                                    >
                                                        <TableCell>
                                                            <Badge
                                                                variant="outline"
                                                                className="font-medium capitalize"
                                                            >
                                                                {
                                                                    m.movement_type
                                                                }
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell className="text-sm whitespace-nowrap">
                                                            {formatDate(
                                                                m.moved_at ||
                                                                    null,
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-xs">
                                                            {m.from_branch && (
                                                                <div className="font-medium">
                                                                    {
                                                                        m
                                                                            .from_branch
                                                                            .name
                                                                    }
                                                                </div>
                                                            )}
                                                            {m.from_location && (
                                                                <div className="text-muted-foreground">
                                                                    {
                                                                        m
                                                                            .from_location
                                                                            .name
                                                                    }
                                                                </div>
                                                            )}
                                                            {m.from_employee && (
                                                                <div className="text-primary">
                                                                    {
                                                                        m
                                                                            .from_employee
                                                                            .name
                                                                    }
                                                                </div>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-xs">
                                                            {m.to_branch && (
                                                                <div className="font-medium">
                                                                    {
                                                                        m
                                                                            .to_branch
                                                                            .name
                                                                    }
                                                                </div>
                                                            )}
                                                            {m.to_location && (
                                                                <div className="text-muted-foreground">
                                                                    {
                                                                        m
                                                                            .to_location
                                                                            .name
                                                                    }
                                                                </div>
                                                            )}
                                                            {m.to_employee && (
                                                                <div className="text-primary">
                                                                    {
                                                                        m
                                                                            .to_employee
                                                                            .name
                                                                    }
                                                                </div>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="max-w-[200px]">
                                                            {m.reference && (
                                                                <div className="text-xs font-semibold">
                                                                    {
                                                                        m.reference
                                                                    }
                                                                </div>
                                                            )}
                                                            {m.notes && (
                                                                <div className="truncate text-xs text-muted-foreground">
                                                                    {m.notes}
                                                                </div>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-xs whitespace-nowrap">
                                                            {m.created_by}
                                                        </TableCell>
                                                    </TableRow>
                                                ),
                                            )}
                                        </TableBody>
                                    </Table>
                                ) : (
                                    <div className="flex flex-col items-center justify-center py-16 text-center">
                                        <div className="mb-4 rounded-full bg-muted p-4">
                                            <History className="h-8 w-8 text-muted-foreground" />
                                        </div>
                                        <h3 className="mb-1 text-lg font-medium">
                                            No Movement History
                                        </h3>
                                        <p className="max-w-sm text-sm text-muted-foreground">
                                            This asset has not been transferred
                                            or reassigned yet.
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Maintenance Tab */}
                    <TabsContent value="maintenance" className="mt-6">
                        <Card className="overflow-hidden">
                            <CardContent className="p-0">
                                {item.maintenances?.length ? (
                                    <Table>
                                        <TableHeader>
                                            <TableRow className="bg-muted/50">
                                                <TableHead>Type</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead>Date</TableHead>
                                                <TableHead>Supplier</TableHead>
                                                <TableHead className="text-right">
                                                    Cost
                                                </TableHead>
                                                <TableHead>Notes</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {item.maintenances?.map(
                                                (m: AssetMaintenance) => (
                                                    <TableRow
                                                        key={m.id}
                                                        className="hover:bg-muted/30"
                                                    >
                                                        <TableCell>
                                                            <Badge
                                                                variant="outline"
                                                                className="font-medium capitalize"
                                                            >
                                                                {
                                                                    m.maintenance_type
                                                                }
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge
                                                                variant={
                                                                    m.status ===
                                                                    'completed'
                                                                        ? 'default'
                                                                        : 'secondary'
                                                                }
                                                                className="capitalize"
                                                            >
                                                                {m.status}
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell className="text-xs whitespace-nowrap">
                                                            <div className="flex items-center gap-1">
                                                                <Calendar className="h-3 w-3 text-muted-foreground" />
                                                                {formatDate(
                                                                    m.scheduled_at,
                                                                )}
                                                            </div>
                                                            {m.performed_at && (
                                                                <div className="mt-1 flex items-center gap-1 text-muted-foreground">
                                                                    <Clock className="h-3 w-3" />
                                                                    {formatDate(
                                                                        m.performed_at,
                                                                    )}
                                                                </div>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-sm">
                                                            {m.supplier || '-'}
                                                        </TableCell>
                                                        <TableCell className="text-right font-medium">
                                                            {formatCurrency(
                                                                m.cost,
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="max-w-[200px] truncate text-xs text-muted-foreground">
                                                            {m.notes}
                                                        </TableCell>
                                                    </TableRow>
                                                ),
                                            )}
                                        </TableBody>
                                    </Table>
                                ) : (
                                    <div className="flex flex-col items-center justify-center py-16 text-center">
                                        <div className="mb-4 rounded-full bg-muted p-4">
                                            <Wrench className="h-8 w-8 text-muted-foreground" />
                                        </div>
                                        <h3 className="mb-1 text-lg font-medium">
                                            No Maintenance Records
                                        </h3>
                                        <p className="max-w-sm text-sm text-muted-foreground">
                                            This asset has no scheduled or
                                            completed maintenance tasks.
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Stocktake Tab */}
                    <TabsContent value="stocktake" className="mt-6">
                        <Card className="overflow-hidden">
                            <CardContent className="p-0">
                                {item.stocktake_items?.length ? (
                                    <Table>
                                        <TableHeader>
                                            <TableRow className="bg-muted/50">
                                                <TableHead>Reference</TableHead>
                                                <TableHead>Date</TableHead>
                                                <TableHead>Branch</TableHead>
                                                <TableHead>
                                                    Expect/Found
                                                </TableHead>
                                                <TableHead>Result</TableHead>
                                                <TableHead>Notes</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {item.stocktake_items?.map(
                                                (s: AssetStocktakeItem) => (
                                                    <TableRow
                                                        key={s.id}
                                                        className="hover:bg-muted/30"
                                                    >
                                                        <TableCell className="font-mono font-medium">
                                                            {
                                                                s.stocktake_reference
                                                            }
                                                        </TableCell>
                                                        <TableCell className="text-sm whitespace-nowrap">
                                                            {s.stocktake_date}
                                                        </TableCell>
                                                        <TableCell>
                                                            {s.branch}
                                                        </TableCell>
                                                        <TableCell className="text-xs">
                                                            <div className="flex items-center gap-1 text-muted-foreground">
                                                                <AlertCircle className="h-3 w-3" />
                                                                {
                                                                    s.expected_location
                                                                }
                                                            </div>
                                                            <div className="mt-1 flex items-center gap-1 font-medium text-primary">
                                                                <MapPin className="h-3 w-3" />
                                                                {
                                                                    s.found_location
                                                                }
                                                            </div>
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge
                                                                variant={
                                                                    s.result ===
                                                                    'found'
                                                                        ? 'default'
                                                                        : 'destructive'
                                                                }
                                                                className="capitalize"
                                                            >
                                                                {s.result}
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell className="max-w-[200px] truncate text-xs text-muted-foreground">
                                                            {s.notes}
                                                        </TableCell>
                                                    </TableRow>
                                                ),
                                            )}
                                        </TableBody>
                                    </Table>
                                ) : (
                                    <div className="flex flex-col items-center justify-center py-16 text-center">
                                        <div className="mb-4 rounded-full bg-muted p-4">
                                            <ClipboardCheck className="h-8 w-8 text-muted-foreground" />
                                        </div>
                                        <h3 className="mb-1 text-lg font-medium">
                                            No Stocktake Records
                                        </h3>
                                        <p className="max-w-sm text-sm text-muted-foreground">
                                            This asset has not been included in
                                            any stocktake yet.
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Depreciation Tab */}
                    <TabsContent value="depreciation" className="mt-6">
                        <Card className="overflow-hidden">
                            <CardContent className="p-0">
                                {item.depreciation_lines?.length ? (
                                    <Table>
                                        <TableHeader>
                                            <TableRow className="bg-muted/50">
                                                <TableHead>Period</TableHead>
                                                <TableHead>FY</TableHead>
                                                <TableHead className="text-right">
                                                    Amount
                                                </TableHead>
                                                <TableHead className="text-right">
                                                    Accum. (After)
                                                </TableHead>
                                                <TableHead className="text-right">
                                                    Book Value
                                                </TableHead>
                                                <TableHead>Status</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {item.depreciation_lines?.map(
                                                (d: AssetDepreciationLine) => (
                                                    <TableRow
                                                        key={d.id}
                                                        className="hover:bg-muted/30"
                                                    >
                                                        <TableCell className="font-mono text-sm">
                                                            {d.period}
                                                        </TableCell>
                                                        <TableCell className="text-sm">
                                                            {d.fiscal_year}
                                                        </TableCell>
                                                        <TableCell className="text-right font-medium">
                                                            {formatCurrency(
                                                                d.amount || 0,
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-right text-sm text-muted-foreground">
                                                            {formatCurrency(
                                                                d.accumulated_after ||
                                                                    0,
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-right font-semibold text-primary">
                                                            {formatCurrency(
                                                                d.book_value_after ||
                                                                    0,
                                                            )}
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge
                                                                variant={
                                                                    d.status ===
                                                                    'posted'
                                                                        ? 'default'
                                                                        : 'outline'
                                                                }
                                                                className="text-xs capitalize"
                                                            >
                                                                {d.status}
                                                            </Badge>
                                                        </TableCell>
                                                    </TableRow>
                                                ),
                                            )}
                                        </TableBody>
                                    </Table>
                                ) : (
                                    <div className="flex flex-col items-center justify-center py-16 text-center">
                                        <div className="mb-4 rounded-full bg-muted p-4">
                                            <TrendingDown className="h-8 w-8 text-muted-foreground" />
                                        </div>
                                        <h3 className="mb-1 text-lg font-medium">
                                            No Depreciation History
                                        </h3>
                                        <p className="max-w-sm text-sm text-muted-foreground">
                                            No depreciation has been calculated
                                            for this asset yet.
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Timeline Tab */}
                    <TabsContent value="timeline" className="mt-6">
                        <EntityStateTimeline
                            key={timelineKey}
                            entityType="asset"
                            entityId={item.ulid}
                        />
                    </TabsContent>

                    {/* Approvals Tab */}
                    <TabsContent value="approvals" className="mt-6">
                        <ApprovalHistoryTimeline
                            entityType="asset"
                            entityId={item.ulid}
                        />
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
