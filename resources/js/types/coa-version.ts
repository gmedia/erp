import { type EntityWithId } from './entity';

export interface CoaVersion extends EntityWithId {
    name: string;
    fiscal_year_id: number;
    fiscal_year?: {
        id: number;
        name: string;
    };
    status: 'draft' | 'active' | 'archived';
    created_at: string;
    updated_at: string;
}
