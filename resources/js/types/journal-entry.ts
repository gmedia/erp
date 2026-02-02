export interface JournalEntryLine {
    id?: number;
    account_id: number;
    account_name?: string;
    account_code?: string;
    debit: number;
    credit: number;
    memo?: string;
}

export interface JournalEntry {
    id: number;
    entry_number: string;
    entry_date: string;
    reference?: string;
    description: string;
    fiscal_year_id: number;
    fiscal_year: {
        id: number;
        name: string;
    };
    status: 'draft' | 'posted' | 'void';
    lines: JournalEntryLine[];
    total_debit: number;
    total_credit: number;
    created_by: {
        id: number;
        name: string;
    };
    created_at: string;
    updated_at: string;
}

export interface JournalEntryFormData {
    entry_date: string;
    reference: string;
    description: string;
    lines: JournalEntryLine[];
}
