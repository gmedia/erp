import { EntityWithId } from './entity';

export interface AssetDepreciationRun extends EntityWithId {
    fiscal_year_id: number;
    fiscal_year?: {
        id: number;
        name: string;
    };
    period_start: string;
    period_end: string;
    status: 'draft' | 'calculated' | 'posted' | 'void';
    journal_entry_id: number | null;
    journal_entry?: {
        id: number;
        entry_number: string;
    };
    created_by: number | null;
    created_by_user?: {
        id: number;
        name: string;
    };
    posted_by: number | null;
    posted_by_user?: {
        id: number;
        name: string;
    };
    posted_at: string | null;
    lines_count?: number;
}

export interface AssetDepreciationLine extends EntityWithId {
    asset_depreciation_run_id: number;
    asset_id: number;
    asset?: {
        id: number;
        name: string;
        asset_code: string;
    };
    amount: number;
    accumulated_before: number;
    accumulated_after: number;
    book_value_after: number;
}
