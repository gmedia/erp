export type ReportType =
    | 'balance_sheet'
    | 'income_statement'
    | 'cash_flow'
    | 'trial_balance'
    | 'custom';

export type SectionType =
    | 'header'
    | 'detail'
    | 'subtotal'
    | 'total'
    | 'separator';

export type SignConvention = 'normal' | 'reversed';

export interface ReportSection {
    id?: number;
    parent_id?: number | null;
    code: string;
    name: string;
    sort_order: number;
    section_type: SectionType;
    account_type_filter?: string | null;
    account_sub_type_filter?: string | null;
    sign_convention: SignConvention;
    formula?: string | null;
    is_active: boolean;
    parent_code?: string | null;
}

export interface ReportConfiguration {
    id: number;
    code: string;
    name: string;
    description?: string | null;
    report_type: ReportType;
    layout_config?: Record<string, unknown> | null;
    is_active: boolean;
    sections: ReportSection[];
    created_by?: {
        id: number;
        name: string;
    } | null;
    created_at: string;
    updated_at: string;
}
