'use client';

import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import { type Account } from '@/types/account';
import {
    ChevronDown,
    ChevronRight,
    Edit,
    FileText,
    Folder,
    Plus,
    Trash2,
} from 'lucide-react';
import { useMemo, useState } from 'react';

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
                    'group flex items-center rounded-md border-b border-transparent px-2 py-2 transition-colors hover:bg-accent/50',
                    !account.is_active && 'opacity-60',
                )}
                style={{ paddingLeft: `${level * 20}px` }}
            >
                <div className="flex min-w-0 flex-1 items-center">
                    <button
                        onClick={() => setIsOpen(!isOpen)}
                        className={cn(
                            'mr-1 rounded-sm p-1 transition-transform hover:bg-accent',
                            !hasChildren && 'invisible',
                        )}
                    >
                        {isOpen ? (
                            <ChevronDown className="h-4 w-4" />
                        ) : (
                            <ChevronRight className="h-4 w-4" />
                        )}
                    </button>

                    {hasChildren ? (
                        <Folder className="mr-2 h-4 w-4 shrink-0 text-primary" />
                    ) : (
                        <FileText className="mr-2 h-4 w-4 shrink-0 text-muted-foreground" />
                    )}

                    <div className="flex min-w-0 flex-col">
                        <div className="flex items-center gap-2">
                            <span className="rounded bg-muted px-1.5 py-0.5 font-mono text-xs font-semibold">
                                {account.code}
                            </span>
                            <span className="truncate font-medium">
                                {account.name}
                            </span>
                        </div>
                        <div className="flex items-center gap-2 text-[10px] tracking-wider text-muted-foreground uppercase">
                            <span>{account.type}</span>
                            <span>•</span>
                            <span>{account.normal_balance}</span>
                        </div>
                    </div>
                </div>

                <div className="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                    <TooltipProvider>
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    className="h-8 w-8 text-primary"
                                    onClick={() => onAddChild(account)}
                                >
                                    <Plus className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Add Child Account</TooltipContent>
                        </Tooltip>

                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    className="h-8 w-8 text-blue-500"
                                    onClick={() => onEdit(account)}
                                >
                                    <Edit className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Edit Account</TooltipContent>
                        </Tooltip>

                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    className="h-8 w-8 text-destructive"
                                    onClick={() => onDelete(account)}
                                >
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
                            children={allAccounts.filter(
                                (a) => a.parent_id === child.id,
                            )}
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
            <div className="flex items-center justify-between px-2">
                <h3 className="text-sm font-semibold tracking-wider text-muted-foreground uppercase">
                    Hierarchy
                </h3>
                <Button size="sm" variant="outline" onClick={onAddRoot}>
                    <Plus className="mr-2 h-4 w-4" />
                    New Root Account
                </Button>
            </div>

            <div className="overflow-hidden rounded-lg border bg-card">
                {rootAccounts.length === 0 ? (
                    <div className="p-8 text-center">
                        <p className="mb-4 text-muted-foreground">
                            No accounts found in this version.
                        </p>
                        <Button onClick={onAddRoot}>
                            <Plus className="mr-2 h-4 w-4" />
                            Create Your First Account
                        </Button>
                    </div>
                ) : (
                    <div className="flex flex-col p-2">
                        {rootAccounts.map((account) => (
                            <AccountTreeNode
                                key={account.id}
                                account={account}
                                children={accounts.filter(
                                    (a) => a.parent_id === account.id,
                                )}
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
