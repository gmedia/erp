export interface RecurringJournalLine {
    id?: number;
    account_id: number;
    account_name?: string;
    account_code?: string;
    debit: number;
    credit: number;
    memo?: string;
}

export interface RecurringJournal {
    id: number;
    name: string;
    frequency: 'daily' | 'weekly' | 'monthly' | 'quarterly' | 'yearly';
    next_run_date: string;
    last_run_date?: string;
    auto_post: boolean;
    is_active: boolean;
    reference_template?: string;
    description_template: string;
    lines: RecurringJournalLine[];
    total_amount: number;
    created_by: {
        id: number;
        name: string;
    };
    created_at: string;
    updated_at: string;
}
