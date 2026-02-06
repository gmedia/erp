import { type EntityWithId } from './entity';

export type AccountMappingType = 'merge' | 'split' | 'rename';

export interface AccountMapping extends EntityWithId {
    source_account_id: number;
    target_account_id: number;
    type: AccountMappingType;
    notes?: string | null;
    source_account?: {
        id: number;
        code: string;
        name: string;
        coa_version_id: number;
        coa_version?: {
            id: number;
            name: string;
            status: 'draft' | 'active' | 'archived';
        } | null;
    } | null;
    target_account?: {
        id: number;
        code: string;
        name: string;
        coa_version_id: number;
        coa_version?: {
            id: number;
            name: string;
            status: 'draft' | 'active' | 'archived';
        } | null;
    } | null;
}
