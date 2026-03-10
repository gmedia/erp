// Base entity interfaces with stricter typing
export interface BaseEntity {
    readonly id: number;
    readonly created_at: string;
    readonly updated_at: string;
}

export interface SimpleEntity extends BaseEntity {
    name: string;
}

export interface SimpleEntityFormData {
    name: string;
}

export interface SimpleEntityFilters {
    search: string;
}

// Common entity patterns
export type EntityWithId<T = Record<string, unknown>> = T & BaseEntity;
export type EntityFormData<T = Record<string, unknown>> = T;
export type EntityFilters<T = Record<string, unknown>> = T & {
    search?: string;
};

// Re-export entity types for convenience
export type {
    ApprovalDelegation,
    ApprovalDelegationFilters,
    ApprovalDelegationFormData,
} from './approval-delegation';
export type {
    AssetLocation,
    AssetLocationFilters,
    AssetLocationFormData,
} from './asset-location';
export type {
    AssetModel,
    AssetModelFilters,
    AssetModelFormData,
} from './asset-model';
export type { Customer, CustomerFormData } from './customer';
export type { Department, DepartmentFormData } from './department';
export type { Employee, EmployeeFormData } from './employee';
export type {
    FiscalYear,
    FiscalYearFilters,
    FiscalYearFormData,
} from './fiscal-year';
export type { Pipeline } from './pipeline';
export type { Position, PositionFormData } from './position';
export type { Product, ProductFormData } from './product';
export type { FiscalYear, FiscalYearFormData, FiscalYearFilters } from './fiscal-year';
export type { AssetModel, AssetModelFormData, AssetModelFilters } from './asset-model';
export type { AssetLocation, AssetLocationFormData, AssetLocationFilters } from './asset-location';
export type { Pipeline } from './pipeline';
export type { ApprovalDelegation, ApprovalDelegationFormData, ApprovalDelegationFilters } from './approval-delegation';
export type { PurchaseRequest, PurchaseRequestFormData, PurchaseRequestFilters } from './purchase-request';
export type { PurchaseOrder, PurchaseOrderFormData, PurchaseOrderFilters } from './purchase-order';
export type { GoodsReceipt, GoodsReceiptFormData, GoodsReceiptFilters } from './goods-receipt';
export type { SupplierReturn, SupplierReturnFormData, SupplierReturnFilters } from './supplier-return';
export type { Supplier, SupplierFilters, SupplierFormData } from './supplier';
export type {
    Warehouse,
    WarehouseFilters,
    WarehouseFormData,
} from './warehouse';

export interface ApprovalFlowStep {
    id?: number;
    approval_flow_id?: number;
    step_order: number;
    name: string;
    approver_type: 'user' | 'role' | 'department_head';
    approver_user_id: number | null;
    approver_role_id: number | null;
    approver_department_id: number | null;
    required_action: 'approve' | 'review' | 'acknowledge';
    auto_approve_after_hours: number | null;
    escalate_after_hours: number | null;
    escalation_user_id: number | null;
    can_reject: boolean;
    user?: { id: number; name: string };
    department?: { id: number; name: string };
}

export interface ApprovalFlow {
    id: number;
    code: string;
    name: string;
    approvable_type: string;
    description: string | null;
    is_active: boolean;
    conditions: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
    creator?: { id: number; name: string };
    steps?: ApprovalFlowStep[];
}
