'use client';

import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Badge } from '@/components/ui/badge';
import { type Account } from '@/types/account';

interface AccountViewModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    account: Account | null;
}

export function AccountViewModal({
    open,
    onOpenChange,
    account,
}: Readonly<AccountViewModalProps>) {
    if (!account) return null;

    return (
        <ViewModalShell
            open={open}
            onClose={() => onOpenChange(false)}
            title={
                <>
                    {account.code} - {account.name}
                </>
            }
            description={`Account details for ${account.code}`}
            contentClassName="sm:max-w-[500px]"
        >
                <div className="grid gap-4 py-4">
                    <div className="grid grid-cols-1 gap-1 sm:grid-cols-4 sm:items-center sm:gap-4">
                        <span className="text-sm font-semibold">Type:</span>
                        <span className="capitalize sm:col-span-3">
                            {account.type}
                        </span>
                    </div>
                    <div className="grid grid-cols-1 gap-1 sm:grid-cols-4 sm:items-center sm:gap-4">
                        <span className="text-sm font-semibold">Balance:</span>
                        <Badge
                            variant="secondary"
                            className="w-fit capitalize sm:col-span-3"
                        >
                            {account.normal_balance}
                        </Badge>
                    </div>
                    <div className="grid grid-cols-1 gap-1 sm:grid-cols-4 sm:items-center sm:gap-4">
                        <span className="text-sm font-semibold">Status:</span>
                        <Badge
                            variant={
                                account.is_active ? 'default' : 'destructive'
                            }
                            className="w-fit sm:col-span-3"
                        >
                            {account.is_active ? 'Active' : 'Inactive'}
                        </Badge>
                    </div>
                    <div className="grid grid-cols-1 gap-1 sm:grid-cols-4 sm:items-center sm:gap-4">
                        <span className="text-sm font-semibold">
                            Cash Flow:
                        </span>
                        <span className="sm:col-span-3">
                            {account.is_cash_flow ? 'Yes' : 'No'}
                        </span>
                    </div>
                    {account.description && (
                        <div className="grid grid-cols-1 gap-1 sm:grid-cols-4 sm:items-start sm:gap-4">
                            <span className="text-sm font-semibold">
                                Description:
                            </span>
                            <p className="text-sm text-muted-foreground sm:col-span-3">
                                {account.description}
                            </p>
                        </div>
                    )}
                </div>
        </ViewModalShell>
    );
}
