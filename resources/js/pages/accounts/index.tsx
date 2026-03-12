'use client';

import axiosInstance from '@/lib/axios';
import { Download, Loader2, Search } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { Helmet } from 'react-helmet-async';

import {
    AccountForm,
    type AccountFormData,
} from '@/components/accounts/AccountForm';
import { AccountTree } from '@/components/accounts/AccountTree';
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
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useExport } from '@/hooks/useExport';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type Account } from '@/types/account';
import { type CoaVersion } from '@/types/coa-version';
import axios from 'axios';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Chart of Accounts',
        href: '/accounts',
    },
];

export default function AccountIndex() {
    const [coaVersions, setCoaVersions] = useState<CoaVersion[]>([]);
    const [selectedVersionId, setSelectedVersionId] = useState<string | null>(
        null,
    );
    const [accounts, setAccounts] = useState<Account[]>([]);
    const [search, setSearch] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [isActionLoading, setIsActionLoading] = useState(false);
    const { exporting, exportData } = useExport({
        endpoint: '/api/accounts/export',
    });

    // Form states
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [selectedAccount, setSelectedAccount] = useState<Account | null>(
        null,
    );
    const [parentAccount, setParentAccount] = useState<Account | null>(null);

    // Delete states
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
    const [accountToDelete, setAccountToDelete] = useState<Account | null>(
        null,
    );

    const fetchCoaVersions = useCallback(async () => {
        try {
            const response = await axiosInstance.get(
                '/api/coa-versions?per_page=100',
            );
            const data = response.data.data;
            setCoaVersions(data);
            if (data.length > 0 && !selectedVersionId) {
                // Select active version if exists, otherwise first one
                const activeVersion = data.find(
                    (v: CoaVersion) => v.status === 'active',
                );
                setSelectedVersionId((activeVersion || data[0]).id.toString());
            }
        } catch {
            toast.error('Failed to fetch COA versions');
        }
    }, [selectedVersionId]);

    const fetchAccounts = useCallback(
        async (searchTerm = search) => {
            if (!selectedVersionId) return;
            setIsLoading(true);
            try {
                const response = await axiosInstance.get('/api/accounts', {
                    params: {
                        coa_version_id: selectedVersionId,
                        search: searchTerm,
                    },
                });
                setAccounts(response.data.data);
            } catch {
                toast.error('Failed to fetch accounts');
            } finally {
                setIsLoading(false);
            }
        },
        [selectedVersionId, search],
    );

    useEffect(() => {
        fetchCoaVersions();
    }, [fetchCoaVersions]);

    useEffect(() => {
        if (selectedVersionId) {
            fetchAccounts();
        }
    }, [selectedVersionId, fetchAccounts]);

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

    const handleSubmit = async (data: AccountFormData) => {
        setIsActionLoading(true);
        try {
            if (selectedAccount) {
                await axiosInstance.put(
                    `/api/accounts/${selectedAccount.id}`,
                    data,
                );
                toast.success('Account updated successfully');
            } else {
                await axiosInstance.post('/api/accounts', data);
                toast.success('Account created successfully');
            }
            setIsFormOpen(false);
            fetchAccounts();
        } catch (error: unknown) {
            if (axios.isAxiosError(error)) {
                toast.error(
                    error.response?.data?.message || 'Failed to save account',
                );
            } else {
                toast.error('An unexpected error occurred');
            }
        } finally {
            setIsActionLoading(false);
        }
    };

    const confirmDelete = async () => {
        if (!accountToDelete) return;
        setIsActionLoading(true);
        try {
            await axiosInstance.delete(`/api/accounts/${accountToDelete.id}`);
            toast.success('Account deleted successfully');
            setIsDeleteDialogOpen(false);
            fetchAccounts();
        } catch (error: unknown) {
            if (axios.isAxiosError(error)) {
                toast.error(
                    error.response?.data?.message || 'Failed to delete account',
                );
            } else {
                toast.error('An unexpected error occurred');
            }
        } finally {
            setIsActionLoading(false);
        }
    };

    const handleExport = () => {
        exportData({
            coa_version_id: selectedVersionId || undefined,
            search: search,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Helmet>
                <title>Chart of Accounts</title>
            </Helmet>

            <div className="flex h-full flex-col space-y-6 p-4">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Chart of Accounts
                        </h1>
                        <p className="text-muted-foreground">
                            Manage your hierarchical accounts and COA versions.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Select
                            value={selectedVersionId || ''}
                            onValueChange={setSelectedVersionId}
                        >
                            <SelectTrigger className="w-[320px]">
                                <SelectValue placeholder="Select COA Version" />
                            </SelectTrigger>
                            <SelectContent>
                                {coaVersions.map((v) => (
                                    <SelectItem
                                        key={v.id}
                                        value={v.id.toString()}
                                    >
                                        {v.name} ({v.status})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Button
                            variant="outline"
                            size="icon"
                            onClick={handleExport}
                            disabled={!selectedVersionId || exporting}
                        >
                            {exporting ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                                <Download className="h-4 w-4" />
                            )}
                        </Button>
                    </div>
                </div>

                <div className="flex max-w-md items-center gap-2">
                    <div className="relative flex-1">
                        <Search className="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            placeholder="Search code or name..."
                            className="pl-8"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) =>
                                e.key === 'Enter' && fetchAccounts()
                            }
                        />
                    </div>
                    <Button onClick={() => fetchAccounts()}>Search</Button>
                </div>

                {isLoading ? (
                    <div className="flex flex-col items-center justify-center space-y-4 p-20">
                        <Loader2 className="h-8 w-8 animate-spin text-primary" />
                        <p className="text-muted-foreground">
                            Loading accounts...
                        </p>
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

            <AlertDialog
                open={isDeleteDialogOpen}
                onOpenChange={setIsDeleteDialogOpen}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            This will permanently delete the account{' '}
                            <strong>
                                {accountToDelete?.code} -{' '}
                                {accountToDelete?.name}
                            </strong>
                            . This action cannot be undone.
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
                            {isActionLoading && (
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            )}
                            Delete
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
