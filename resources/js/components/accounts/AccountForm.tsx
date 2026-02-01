'use client';

import * as React from 'react';
import { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import axios from 'axios';

import EntityForm from '@/components/common/EntityForm';
import { type Account, type AccountType, type NormalBalance } from '@/types/account';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

const accountSchema = z.object({
    coa_version_id: z.number(),
    parent_id: z.number().nullable(),
    code: z.string().min(1, 'Code is required'),
    name: z.string().min(1, 'Name is required'),
    type: z.enum(['asset', 'liability', 'equity', 'revenue', 'expense']),
    sub_type: z.string().optional().nullable(),
    normal_balance: z.enum(['debit', 'credit']),
    level: z.number().min(1),
    is_active: z.boolean().default(true),
    is_cash_flow: z.boolean().default(false),
    description: z.string().optional().nullable(),
});

type AccountFormData = z.infer<typeof accountSchema>;

interface AccountFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    coaVersionId: number;
    parentAccount?: Account | null;
    account?: Account | null;
    onSubmit: (data: AccountFormData) => void;
    isLoading?: boolean;
}

export function AccountForm({
    open,
    onOpenChange,
    coaVersionId,
    parentAccount,
    account,
    onSubmit,
    isLoading = false,
}: AccountFormProps) {
    const form = useForm<AccountFormData>({
        resolver: zodResolver(accountSchema) as any,
        defaultValues: {
            coa_version_id: coaVersionId,
            parent_id: null,
            code: '',
            name: '',
            type: 'asset',
            sub_type: '',
            normal_balance: 'debit',
            level: 1,
            is_active: true,
            is_cash_flow: false,
            description: '',
        },
    });

    useEffect(() => {
        if (open) {
            if (account) {
                form.reset({
                    coa_version_id: account.coa_version_id,
                    parent_id: account.parent_id,
                    code: account.code,
                    name: account.name,
                    type: account.type,
                    sub_type: account.sub_type || '',
                    normal_balance: account.normal_balance,
                    level: account.level,
                    is_active: account.is_active,
                    is_cash_flow: account.is_cash_flow,
                    description: account.description || '',
                });
            } else if (parentAccount) {
                form.reset({
                    coa_version_id: coaVersionId,
                    parent_id: parentAccount.id,
                    code: '',
                    name: '',
                    type: parentAccount.type,
                    sub_type: '',
                    normal_balance: parentAccount.normal_balance,
                    level: parentAccount.level + 1,
                    is_active: true,
                    is_cash_flow: false,
                    description: '',
                });
            } else {
                form.reset({
                    coa_version_id: coaVersionId,
                    parent_id: null,
                    code: '',
                    name: '',
                    type: 'asset',
                    sub_type: '',
                    normal_balance: 'debit',
                    level: 1,
                    is_active: true,
                    is_cash_flow: false,
                    description: '',
                });
            }
        }
    }, [open, account, parentAccount, coaVersionId, form]);

    return (
        <EntityForm
            open={open}
            onOpenChange={onOpenChange}
            title={account ? 'Edit Account' : 'Create Account'}
            form={form}
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            <div className="grid gap-4 py-4">
                {parentAccount && (
                    <div className="rounded-md bg-muted p-2 text-sm">
                        Parent: <strong>{parentAccount.code} - {parentAccount.name}</strong>
                    </div>
                )}
                
                <div className="grid grid-cols-4 items-center gap-4">
                    <Label htmlFor="code" className="text-right">Code</Label>
                    <div className="col-span-3">
                        <Input id="code" {...form.register('code')} />
                        {form.formState.errors.code && (
                            <p className="mt-1 text-xs text-destructive">{form.formState.errors.code.message}</p>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-4 items-center gap-4">
                    <Label htmlFor="name" className="text-right">Name</Label>
                    <div className="col-span-3">
                        <Input id="name" {...form.register('name')} />
                        {form.formState.errors.name && (
                            <p className="mt-1 text-xs text-destructive">{form.formState.errors.name.message}</p>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-4 items-center gap-4">
                    <Label htmlFor="type" className="text-right">Type</Label>
                    <div className="col-span-3">
                        <Select 
                            value={form.watch('type')} 
                            onValueChange={(val) => form.setValue('type', val as AccountType)}
                            disabled={!!parentAccount}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="asset">Asset</SelectItem>
                                <SelectItem value="liability">Liability</SelectItem>
                                <SelectItem value="equity">Equity</SelectItem>
                                <SelectItem value="revenue">Revenue</SelectItem>
                                <SelectItem value="expense">Expense</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="grid grid-cols-4 items-center gap-4">
                    <Label htmlFor="normal_balance" className="text-right">Normal Balance</Label>
                    <div className="col-span-3">
                        <Select 
                            value={form.watch('normal_balance')} 
                            onValueChange={(val) => form.setValue('normal_balance', val as NormalBalance)}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select normal balance" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="debit">Debit</SelectItem>
                                <SelectItem value="credit">Credit</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="grid grid-cols-4 items-center gap-4">
                    <Label htmlFor="is_active" className="text-right">Active</Label>
                    <div className="col-span-3 flex items-center space-x-2">
                        <Checkbox 
                            id="is_active" 
                            checked={form.watch('is_active')}
                            onCheckedChange={(checked) => form.setValue('is_active', !!checked)}
                        />
                        <Label htmlFor="is_active" className="text-sm font-normal">Account is available for transactions</Label>
                    </div>
                </div>

                <div className="grid grid-cols-4 items-center gap-4">
                    <Label htmlFor="is_cash_flow" className="text-right">Cash Flow</Label>
                    <div className="col-span-3 flex items-center space-x-2">
                        <Checkbox 
                            id="is_cash_flow" 
                            checked={form.watch('is_cash_flow')}
                            onCheckedChange={(checked) => form.setValue('is_cash_flow', !!checked)}
                        />
                        <Label htmlFor="is_cash_flow" className="text-sm font-normal">Include in Cash Flow report</Label>
                    </div>
                </div>

                <div className="grid grid-cols-4 items-start gap-4">
                    <Label htmlFor="description" className="text-right mt-2">Description</Label>
                    <div className="col-span-3">
                        <Textarea id="description" {...form.register('description')} rows={3} />
                    </div>
                </div>
            </div>
        </EntityForm>
    );
}
