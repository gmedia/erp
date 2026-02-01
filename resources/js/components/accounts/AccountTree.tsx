'use client';

import * as React from 'react';
import { useState, useMemo } from 'react';
import { ChevronRight, ChevronDown, Plus, Edit, Trash2, Folder, FileText } from 'lucide-react';
import { type Account } from '@/types/account';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

interface AccountTreeNodeProps {
    account: Account;
    children?: Account[];
    allAccounts: Account[];
    level: number;
    onAddChild: (parent: Account) => void;
    onEdit: (account: Account) => void;
    onDelete: (account: Account) => void;
}

function AccountTreeNode({
    account,
    children = [],
    allAccounts,
    level,
    onAddChild,
    onEdit,
    onDelete,
}: AccountTreeNodeProps) {
    const [isOpen, setIsOpen] = useState(true);
    const hasChildren = children.length > 0;

    const sortedChildren = useMemo(() => {
        return [...children].sort((a, b) => a.code.localeCompare(b.code));
    }, [children]);

    return (
        <div className="flex flex-col">
            <div 
                className={cn(
                    "group flex items-center py-2 px-2 hover:bg-accent/50 rounded-md transition-colors border-b border-transparent",
                    !account.is_active && "opacity-60"
                )}
                style={{ paddingLeft: `${level * 20}px` }}
            >
                <div className="flex items-center flex-1 min-w-0">
                    <button
                        onClick={() => setIsOpen(!isOpen)}
                        className={cn(
                            "p-1 hover:bg-accent rounded-sm mr-1 transition-transform",
                            !hasChildren && "invisible"
                        )}
                    >
                        {isOpen ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                    </button>

                    {hasChildren ? (
                        <Folder className="h-4 w-4 text-primary mr-2 shrink-0" />
                    ) : (
                        <FileText className="h-4 w-4 text-muted-foreground mr-2 shrink-0" />
                    )}

                    <div className="flex flex-col min-w-0">
                        <div className="flex items-center gap-2">
                            <span className="font-mono text-xs font-semibold bg-muted px-1.5 py-0.5 rounded">
                                {account.code}
                            </span>
                            <span className="font-medium truncate">{account.name}</span>
                        </div>
                        <div className="flex items-center gap-2 text-[10px] text-muted-foreground uppercase tracking-wider">
                            <span>{account.type}</span>
                            <span>•</span>
                            <span>{account.normal_balance}</span>
                        </div>
                    </div>
                </div>

                <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <TooltipProvider>
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button size="icon" variant="ghost" className="h-8 w-8 text-primary" onClick={() => onAddChild(account)}>
                                    <Plus className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Add Child Account</TooltipContent>
                        </Tooltip>

                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button size="icon" variant="ghost" className="h-8 w-8 text-blue-500" onClick={() => onEdit(account)}>
                                    <Edit className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Edit Account</TooltipContent>
                        </Tooltip>

                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button size="icon" variant="ghost" className="h-8 w-8 text-destructive" onClick={() => onDelete(account)}>
                                    <Trash2 className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Delete Account</TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </div>
            </div>

            {isOpen && hasChildren && (
                <div className="flex flex-col">
                    {sortedChildren.map((child) => (
                        <AccountTreeNode
                            key={child.id}
                            account={child}
                            children={allAccounts.filter(a => a.parent_id === child.id)}
                            allAccounts={allAccounts}
                            level={level + 1}
                            onAddChild={onAddChild}
                            onEdit={onEdit}
                            onDelete={onDelete}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

interface AccountTreeProps {
    accounts: Account[];
    onAddChild: (parent: Account) => void;
    onEdit: (account: Account) => void;
    onDelete: (account: Account) => void;
    onAddRoot: () => void;
}

export function AccountTree({
    accounts,
    onAddChild,
    onEdit,
    onDelete,
    onAddRoot,
}: AccountTreeProps) {
    const rootAccounts = useMemo(() => {
        return accounts
            .filter((a) => a.parent_id === null)
            .sort((a, b) => a.code.localeCompare(b.code));
    }, [accounts]);

    return (
        <div className="flex flex-col space-y-4">
            <div className="flex justify-between items-center px-2">
                <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider">
                    Hierarchy
                </h3>
                <Button size="sm" variant="outline" onClick={onAddRoot}>
                    <Plus className="h-4 w-4 mr-2" />
                    New Root Account
                </Button>
            </div>

            <div className="border rounded-lg bg-card overflow-hidden">
                {rootAccounts.length === 0 ? (
                    <div className="p-8 text-center">
                        <p className="text-muted-foreground mb-4">No accounts found in this version.</p>
                        <Button onClick={onAddRoot}>
                            <Plus className="h-4 w-4 mr-2" />
                            Create Your First Account
                        </Button>
                    </div>
                ) : (
                    <div className="flex flex-col p-2">
                        {rootAccounts.map((account) => (
                            <AccountTreeNode
                                key={account.id}
                                account={account}
                                children={accounts.filter(a => a.parent_id === account.id)}
                                allAccounts={accounts}
                                level={0}
                                onAddChild={onAddChild}
                                onEdit={onEdit}
                                onDelete={onDelete}
                            />
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
