'use client';

import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { usePostingJournal } from '@/hooks/usePostingJournal';
import { BreadcrumbItem } from '@/types';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Loader2, CheckCircle2, AlertCircle, Search } from 'lucide-react';
import { format } from 'date-fns';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Posting Journals',
        href: '/posting-journals',
    },
];

export default function Index() {
    const {
        data,
        isLoading,
        selectedIds,
        toggleSelection,
        selectAll,
        postSelected,
        isPosting,
        handleSearch,
    } = usePostingJournal();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Posting Journals" />

            <div className="flex flex-col gap-6 p-6">
                <Card className="border-none shadow-sm bg-linear-to-br from-white to-gray-50 dark:from-gray-900 dark:to-gray-950">
                    <CardHeader className="flex flex-row items-center justify-between space-y-0">
                        <div>
                            <CardTitle className="text-2xl font-bold tracking-tight">Post Journal Entries</CardTitle>
                            <CardDescription className="text-muted-foreground mt-1">
                                Review and post draft journal entries to the general ledger.
                            </CardDescription>
                        </div>
                        <div className="flex items-center gap-4">
                            <div className="relative w-72">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    placeholder="Search journals..."
                                    className="pl-9 bg-white/50 backdrop-blur-sm border-gray-100 focus:bg-white transition-all"
                                    onChange={(e) => handleSearch(e.target.value)}
                                />
                            </div>
                            <Button
                                onClick={postSelected}
                                disabled={selectedIds.length === 0 || isPosting}
                                className="bg-blue-600 hover:bg-blue-700 text-white transition-all duration-200 shadow-md hover:shadow-lg disabled:opacity-50"
                            >
                                {isPosting ? (
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                ) : (
                                    <CheckCircle2 className="mr-2 h-4 w-4" />
                                )}
                                {isPosting ? 'Posting...' : `Post Selected (${selectedIds.length})`}
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
                            <Table>
                                <TableHeader className="bg-gray-50/50 dark:bg-gray-800/50">
                                    <TableRow>
                                        <TableHead className="w-[50px]">
                                            <Checkbox
                                                checked={data.length > 0 && selectedIds.length === data.length}
                                                onCheckedChange={selectAll}
                                            />
                                        </TableHead>
                                        <TableHead className="font-semibold uppercase text-xs tracking-wider">Journal Info</TableHead>
                                        <TableHead className="font-semibold uppercase text-xs tracking-wider">Details</TableHead>
                                        <TableHead className="text-right font-semibold uppercase text-xs tracking-wider">Debit</TableHead>
                                        <TableHead className="text-right font-semibold uppercase text-xs tracking-wider">Credit</TableHead>
                                        <TableHead className="w-[100px] text-center font-semibold uppercase text-xs tracking-wider">Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {isLoading ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="h-64 text-center">
                                                <div className="flex flex-col items-center justify-center gap-2">
                                                    <Loader2 className="h-8 w-8 animate-spin text-blue-500" />
                                                    <p className="text-muted-foreground animate-pulse">Loading journals...</p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="h-64 text-center">
                                                <div className="flex flex-col items-center justify-center gap-2 opacity-60">
                                                    <AlertCircle className="h-10 w-10 text-gray-400" />
                                                    <p className="text-xl font-medium text-gray-500">No draft journals found</p>
                                                    <p className="text-sm text-gray-400">All journal entries are already posted or voided.</p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        data.map((item) => (
                                            <TableRow key={item.id} className="group hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors border-b last:border-0">
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedIds.includes(item.id)}
                                                        onCheckedChange={() => toggleSelection(item.id)}
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex flex-col gap-1">
                                                        <span className="font-mono font-bold text-blue-600 dark:text-blue-400">{item.entry_number}</span>
                                                        <span className="text-xs text-muted-foreground">{format(new Date(item.entry_date), 'dd MMM yyyy')}</span>
                                                        <p className="text-sm text-gray-700 dark:text-gray-300 italic truncate max-w-[200px]">"{item.description}"</p>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex flex-col gap-2 py-2">
                                                        {item.lines.map((line, idx) => (
                                                            <div key={idx} className="flex items-center justify-between text-xs border-b border-gray-50 dark:border-gray-800 last:border-0 pb-1 last:pb-0 gap-8">
                                                                <div className="flex items-center gap-2">
                                                                    <span className="bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded font-mono text-gray-600 dark:text-gray-400">{line.account_code}</span>
                                                                    <span className="font-medium text-gray-800 dark:text-gray-200">{line.account_name}</span>
                                                                </div>
                                                                <div className="flex gap-4 min-w-[120px] justify-end font-mono">
                                                                    <span className={line.debit > 0 ? 'text-blue-600 dark:text-blue-400 font-semibold' : 'text-gray-300 dark:text-gray-700'}>
                                                                        {line.debit > 0 ? line.debit.toLocaleString() : '-'}
                                                                    </span>
                                                                    <span className={line.credit > 0 ? 'text-amber-600 dark:text-amber-400 font-semibold' : 'text-gray-300 dark:text-gray-700'}>
                                                                        {line.credit > 0 ? line.credit.toLocaleString() : '-'}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-right align-top pt-4">
                                                    <span className="font-bold text-sm text-blue-600 dark:text-blue-400">
                                                        {item.total_debit.toLocaleString()}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-right align-top pt-4">
                                                    <span className="font-bold text-sm text-amber-600 dark:text-amber-400">
                                                        {item.total_credit.toLocaleString()}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-center align-top pt-4">
                                                    <Badge variant="secondary" className="bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 border-none capitalize">
                                                        {item.status}
                                                    </Badge>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
