'use client';

import * as React from 'react';
import { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { Search, Loader2, Download } from 'lucide-react';

import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type Account } from '@/types/account';
import { type CoaVersion } from '@/types/coa-version';
import { AccountTree } from '@/components/accounts/AccountTree';
import { AccountForm } from '@/components/accounts/AccountForm';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { toast } from 'sonner';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Chart of Accounts',
        href: '/accounts',
    },
];

export default function AccountIndex() {
    const [coaVersions, setCoaVersions] = useState<CoaVersion[]>([]);
    const [selectedVersionId, setSelectedVersionId] = useState<string | null>(null);
    const [accounts, setAccounts] = useState<Account[]>([]);
    const [search, setSearch] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [isActionLoading, setIsActionLoading] = useState(false);

    // Form states
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [selectedAccount, setSelectedAccount] = useState<Account | null>(null);
    const [parentAccount, setParentAccount] = useState<Account | null>(null);

    // Delete states
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
    const [accountToDelete, setAccountToDelete] = useState<Account | null>(null);

    useEffect(() => {
        fetchCoaVersions();
    }, []);

    useEffect(() => {
        if (selectedVersionId) {
            fetchAccounts();
        }
    }, [selectedVersionId]);

    const fetchCoaVersions = async () => {
        try {
            const response = await axios.get('/api/coa-versions?per_page=100');
            const data = response.data.data;
            setCoaVersions(data);
            if (data.length > 0 && !selectedVersionId) {
                // Select active version if exists, otherwise first one
                const activeVersion = data.find((v: CoaVersion) => v.status === 'active');
                setSelectedVersionId((activeVersion || data[0]).id.toString());
            }
        } catch (error) {
            toast.error('Failed to fetch COA versions');
        }
    };

    const fetchAccounts = async (searchTerm = search) => {
        if (!selectedVersionId) return;
        setIsLoading(true);
        try {
            const response = await axios.get('/api/accounts', {
                params: {
                    coa_version_id: selectedVersionId,
                    search: searchTerm,
                },
            });
            setAccounts(response.data.data);
        } catch (error) {
            toast.error('Failed to fetch accounts');
        } finally {
            setIsLoading(false);
        }
    };

    const handleAddRoot = () => {
        setSelectedAccount(null);
        setParentAccount(null);
        setIsFormOpen(true);
    };

    const handleAddChild = (parent: Account) => {
        setSelectedAccount(null);
        setParentAccount(parent);
        setIsFormOpen(true);
    };

    const handleEdit = (account: Account) => {
        setSelectedAccount(account);
        setParentAccount(null);
        setIsFormOpen(true);
    };

    const handleDeleteClick = (account: Account) => {
        setAccountToDelete(account);
        setIsDeleteDialogOpen(true);
    };

    const handleSubmit = async (data: any) => {
        setIsActionLoading(true);
        try {
            if (selectedAccount) {
                await axios.put(`/api/accounts/${selectedAccount.id}`, data);
                toast.success('Account updated successfully');
            } else {
                await axios.post('/api/accounts', data);
                toast.success('Account created successfully');
            }
            setIsFormOpen(false);
            fetchAccounts();
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Failed to save account');
        } finally {
            setIsActionLoading(false);
        }
    };

    const confirmDelete = async () => {
        if (!accountToDelete) return;
        setIsActionLoading(true);
        try {
            await axios.delete(`/api/accounts/${accountToDelete.id}`);
            toast.success('Account deleted successfully');
            setIsDeleteDialogOpen(false);
            fetchAccounts();
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Failed to delete account');
        } finally {
            setIsActionLoading(false);
        }
    };

    const handleExport = async () => {
        try {
            const response = await axios.post('/api/accounts/export', {
                coa_version_id: selectedVersionId,
                search: search,
            });
            
            if (response.data.url) {
                window.location.href = response.data.url;
            }
            
            toast.success(response.data.message || 'Export started');
        } catch (error) {
            toast.error('Failed to export accounts');
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Chart of Accounts" />

            <div className="flex h-full flex-col p-4 space-y-6">
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Chart of Accounts</h1>
                        <p className="text-muted-foreground">Manage your hierarchical accounts and COA versions.</p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Select value={selectedVersionId || ''} onValueChange={setSelectedVersionId}>
                            <SelectTrigger className="w-[320px]">
                                <SelectValue placeholder="Select COA Version" />
                            </SelectTrigger>
                            <SelectContent>
                                {coaVersions.map((v) => (
                                    <SelectItem key={v.id} value={v.id.toString()}>
                                        {v.name} ({v.status})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Button variant="outline" size="icon" onClick={handleExport} disabled={!selectedVersionId}>
                            <Download className="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <div className="flex items-center gap-2 max-w-md">
                    <div className="relative flex-1">
                        <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            placeholder="Search code or name..."
                            className="pl-8"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && fetchAccounts()}
                        />
                    </div>
                    <Button onClick={() => fetchAccounts()}>Search</Button>
                </div>

                {isLoading ? (
                    <div className="flex flex-col items-center justify-center p-20 space-y-4">
                        <Loader2 className="h-8 w-8 animate-spin text-primary" />
                        <p className="text-muted-foreground">Loading accounts...</p>
                    </div>
                ) : (
                    <AccountTree
                        accounts={accounts}
                        onAddRoot={handleAddRoot}
                        onAddChild={handleAddChild}
                        onEdit={handleEdit}
                        onDelete={handleDeleteClick}
                    />
                )}
            </div>

            <AccountForm
                open={isFormOpen}
                onOpenChange={setIsFormOpen}
                coaVersionId={Number(selectedVersionId)}
                parentAccount={parentAccount}
                account={selectedAccount}
                onSubmit={handleSubmit}
                isLoading={isActionLoading}
            />

            <AlertDialog open={isDeleteDialogOpen} onOpenChange={setIsDeleteDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            This will permanently delete the account <strong>{accountToDelete?.code} - {accountToDelete?.name}</strong>.
                            This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction 
                            onClick={(e) => {
                                e.preventDefault();
                                confirmDelete();
                            }}
                            className="bg-destructive text-white hover:bg-destructive/80"
                            disabled={isActionLoading}
                        >
                            {isActionLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            Delete
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
