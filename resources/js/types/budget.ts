export interface Budget {
    id: number;
    ulid: string;
    fiscal_year_id: number;
    name: string;
    description: string | null;
    budget_type: string;
    status: string;
    total_amount: number;
    approved_by: number | null;
    approved_at: string | null;
    created_by: number;
    created_at: string;
    updated_at: string;
    fiscal_year?: { id: number; name: string };
    creator?: { id: number; name: string };
    approver?: { id: number; name: string } | null;
    lines?: BudgetLine[];
}

export interface BudgetLine {
    id?: number;
    account_id: number;
    account?: { id: number; code: string; name: string };
    period_start: string;
    period_end: string;
    allocated_amount: number;
    notes: string | null;
}

export interface BudgetVarianceItem {
    account_id: number;
    account_code: string;
    account_name: string;
    account_type: string;
    period_start: string;
    period_end: string;
    allocated: number;
    actual: number;
    available: number;
    variance_percent: number | null;
    status: 'within_budget' | 'warning' | 'over_budget';
}
