import { type BaseEntity } from './entity';

export interface FiscalYear extends BaseEntity {
    name: string;
    start_date: string;
    end_date: string;
    status: 'open' | 'closed' | 'locked';
}

export interface FiscalYearFormData {
    name: string;
    start_date: string;
    end_date: string;
    status: 'open' | 'closed' | 'locked';
}

export interface FiscalYearFilters {
    search: string;
    status: string;
}
