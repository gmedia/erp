import { BaseEntity } from './entity';

export interface Pipeline extends BaseEntity {
    name: string;
    code: string;
    entity_type: string;
    description: string | null;
    version: number;
    is_active: boolean;
    conditions: string | null;
    created_by: { id: number; name: string } | null;
}
