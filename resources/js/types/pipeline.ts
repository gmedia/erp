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

export interface PipelineTransitionAction extends BaseEntity {
    pipeline_transition_id?: number;
    action_type: 'update_field' | 'create_record' | 'send_notification' | 'dispatch_job' | 'trigger_approval' | 'webhook' | 'custom';
    execution_order: number;
    config: Record<string, any>;
    is_async: boolean;
    on_failure: 'abort' | 'continue' | 'log_and_continue';
    is_active: boolean;
}

export interface PipelineTransition extends BaseEntity {
    pipeline_id: number;
    from_state_id: number;
    to_state_id: number;
    name: string;
    code: string;
    description: string | null;
    required_permission: string | null;
    guard_conditions: Record<string, any> | null;
    requires_confirmation: boolean;
    requires_comment: boolean;
    requires_approval: boolean;
    sort_order: number;
    is_active: boolean;
    from_state?: PipelineState;
    to_state?: PipelineState;
    actions: PipelineTransitionAction[];
}
