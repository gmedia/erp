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
}

export interface BankReconciliation {
    id: number;
    account_id: number;
    account_name?: string;
    account_code?: string;
    fiscal_year_id: number;
    fiscal_year?: {
        id: number;
        name: string;
    };
    period_start: string;
    period_end: string;
    statement_balance: number;
    book_balance: number;
    difference: number;
    status: 'in_progress' | 'completed';
    items: BankReconciliationItem[];
    completed_at?: string;
    completed_by?: {
        id: number;
        name: string;
    };
    created_by: {
        id: number;
        name: string;
    };
    created_at: string;
    updated_at: string;
}
