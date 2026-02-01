import { type EntityWithId } from './entity';

export type AccountType = 'asset' | 'liability' | 'equity' | 'revenue' | 'expense';
export type NormalBalance = 'debit' | 'credit';

export interface Account extends EntityWithId {
    coa_version_id: number;
    parent_id: number | null;
    code: string;
    name: string;
    type: AccountType;
    sub_type: string | null;
    normal_balance: NormalBalance;
    level: number;
    is_active: boolean;
    is_cash_flow: boolean;
    description: string | null;
    created_at: string;
    updated_at: string;
    parent?: Account | null;
}
