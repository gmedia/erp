export interface BankReconciliationItem {
    id?: number;
    journal_entry_id?: number;
    journal_entry_number?: string;
    transaction_date: string;
    description: string;
    debit: number;
    credit: number;
    type: string;
    is_reconciled: boolean;
    reference?: string | null;
    account_id?: number | null;
    account?: {
        id: number;
        code: string;
        name: string;
    } | null;
}

export interface UnmatchedJournalLine {
    id: number;
    account_id: number;
    debit: number;
    credit: number;
    memo: string | null;
    journal_entry: {
        id: number;
        entry_date: string;
        reference: string | null;
        description: string;
        entry_number: string;
    };
}

export interface BankReconciliation {
    id: number;
    account_id: number;
    account?: {
        id: number;
        code: string;
        name: string;
    };
    fiscal_year_id: number;
    fiscal_year?: {
        id: number;
        name: string;
    };
    period_start: string;
    period_end: string;
    statement_balance: number;
    book_balance: number;
    reconciled_balance: number;
    difference: number;
    status: 'in_progress' | 'completed';
    notes?: string | null;
    items: BankReconciliationItem[];
    completed_at?: string | null;
    completed_by?: {
        id: number;
        name: string;
    } | null;
    created_by: {
        id: number;
        name: string;
    };
    created_at: string;
    updated_at: string;
}
