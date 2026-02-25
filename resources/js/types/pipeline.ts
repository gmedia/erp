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

export interface PipelineState extends BaseEntity {
    pipeline_id: number;
    code: string;
    name: string;
    type: 'initial' | 'intermediate' | 'final';
    color: string | null;
    icon: string | null;
    description: string | null;
    sort_order: number;
    metadata: Record<string, unknown> | null;
}
