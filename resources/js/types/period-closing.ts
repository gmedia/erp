export interface PeriodClosing {
    id: number;
    fiscal_year_id: number;
    fiscal_year?: {
        id: number;
        name: string;
    };
    period_month: number;
    period_year: number;
    closing_type: 'monthly' | 'yearly';
    retained_earnings_account_id: number;
    retained_earnings_account?: {
        id: number;
        code: string;
        name: string;
    };
    net_income: number;
    status: 'draft' | 'closed';
    journal_entry_id?: number;
    journal_entry?: {
        id: number;
        entry_number: string;
    };
    closed_at?: string;
    closed_by?: {
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
