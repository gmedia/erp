import { type FormComponentType } from '@/components/common/EntityCrudPage';
import {
    createSimpleEntityFilterFields,
    type FieldDescriptor,
} from '@/components/common/filters';
import { type FilterState } from '@/hooks/useCrudFilters';
import { type BreadcrumbItem } from '@/types';
import {
    type EntityWithId,
    type FiscalYear,
    type Pipeline,
    type Warehouse,
} from '@/types/entity';
import { type ColumnDef, type SortingState } from '@tanstack/react-table';
import * as React from 'react';

// Base configuration interface for all entities with improved typing
export interface BaseEntityConfig<
    T = unknown,
    FilterType extends FilterState = FilterState,
> {
    readonly entityName: string;
    readonly entityNamePlural: string;
    /** Kalimat singkat tujuan halaman di bawah judul (W6-17). */
    readonly description?: string;
    readonly apiEndpoint: string;
    readonly exportEndpoint: string;
    readonly queryKey: readonly string[];
    readonly identifierKey?: string;
    readonly breadcrumbs: readonly BreadcrumbItem[];
    readonly getDeleteMessage: (item: T) => string;
    readonly initialFilters?: FilterType;
    /** Prefix permission modul (`department` -> `.create/.edit/.delete`). Kosong = tanpa gating. */
    readonly permissionPrefix?: string;
}

// Configuration for entities with custom components and stricter typing
export interface CustomEntityConfig<
    T = Record<string, unknown>,
    FilterType extends FilterState = FilterState,
> extends BaseEntityConfig<T, FilterType> {
    // Column definitions for the data table
    readonly columns: ColumnDef<T>[];
    // Filter field descriptors
    readonly filterFields: readonly FieldDescriptor[];
    // Form component (can be a React component or import path)
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    readonly formComponent: React.ComponentType<any>;
    // Form type for proper prop mapping
    readonly formType: FormComponentType;
    // Optional entity name for search placeholder
    readonly entityNameForSearch?: string;
    // Optional view modal component for displaying item details
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    readonly viewModalComponent?: React.ComponentType<any>;
    // Kolom yang disembunyikan default (dapat diaktifkan lewat menu Columns)
    readonly defaultColumnVisibility?: Record<string, boolean>;
    // Urutan awal sama dengan default backend (indikator sort aktif di header)
    readonly defaultSorting?: SortingState;
    // Opt-in: saat Edit, muat detail (GET /:id) sebelum form dibuka. Default off;
    // hanya entity yang item-nya tidak ada di respons list (mis. stock transfer).
    readonly loadDetailOnEdit?: boolean;
    readonly detailLoadErrorMessage?: string;
    readonly titleKey?: string;
    readonly descriptionKey?: string;
}

// Union type for all entity configurations with improved typing
export type EntityConfig<
    T extends EntityWithId = EntityWithId,
    FilterType extends FilterState = FilterState,
> = CustomEntityConfig<T, FilterType>;

import { assetCategoryColumns } from '@/components/asset-categories/AssetCategoryColumns';
import { AssetCategoryForm } from '@/components/asset-categories/AssetCategoryForm';
import { AssetCategoryViewModal } from '@/components/asset-categories/AssetCategoryViewModal';
import { assetMaintenanceColumns } from '@/components/asset-maintenances/AssetMaintenanceColumns';
import { createAssetMaintenanceFilterFields } from '@/components/asset-maintenances/AssetMaintenanceFilters';
import { AssetMaintenanceForm } from '@/components/asset-maintenances/AssetMaintenanceForm';
import { AssetMaintenanceViewModal } from '@/components/asset-maintenances/AssetMaintenanceViewModal';
import { assetMovementColumns } from '@/components/asset-movements/AssetMovementColumns';
import { createAssetMovementFilterFields } from '@/components/asset-movements/AssetMovementFilters';
import { AssetMovementForm } from '@/components/asset-movements/AssetMovementForm';
import { AssetMovementViewModal } from '@/components/asset-movements/AssetMovementViewModal';
import { assetColumns } from '@/components/assets/AssetColumns';
import { createAssetFilterFields } from '@/components/assets/AssetFilters';
import { AssetForm } from '@/components/assets/AssetForm';
import { AssetViewModal } from '@/components/assets/AssetViewModal';
import { SimpleEntityForm } from '@/components/common/EntityForm';
import { SimpleEntityViewModal } from '@/components/common/SimpleEntityViewModal';
import { customerColumns } from '@/components/customers/CustomerColumns';
import { createCustomerFilterFields } from '@/components/customers/CustomerFilters';
import { CustomerForm } from '@/components/customers/CustomerForm';
import { CustomerViewModal } from '@/components/customers/CustomerViewModal';
import { employeeColumns } from '@/components/employees/EmployeeColumns';
import { createEmployeeFilterFields } from '@/components/employees/EmployeeFilters';
import { EmployeeForm } from '@/components/employees/EmployeeForm';
import { EmployeeViewModal } from '@/components/employees/EmployeeViewModal';
import { goodsReceiptColumns } from '@/components/goods-receipts/GoodsReceiptColumns';
import { createGoodsReceiptFilterFields } from '@/components/goods-receipts/GoodsReceiptFilters';
import { GoodsReceiptForm } from '@/components/goods-receipts/GoodsReceiptForm';
import { GoodsReceiptViewModal } from '@/components/goods-receipts/GoodsReceiptViewModal';
import { inventoryStocktakeColumns } from '@/components/inventory-stocktakes/InventoryStocktakeColumns';
import { createInventoryStocktakeFilterFields } from '@/components/inventory-stocktakes/InventoryStocktakeFilters';
import { InventoryStocktakeForm } from '@/components/inventory-stocktakes/InventoryStocktakeForm';
import { InventoryStocktakeViewModal } from '@/components/inventory-stocktakes/InventoryStocktakeViewModal';
import {
    productCategoryColumns,
    type ProductCategory,
} from '@/components/product-categories/ProductCategoryColumns';
import { ProductCategoryForm } from '@/components/product-categories/ProductCategoryForm';
import { ProductCategoryViewModal } from '@/components/product-categories/ProductCategoryViewModal';
import { productColumns } from '@/components/products/ProductColumns';
import { createProductFilterFields } from '@/components/products/ProductFilters';
import { ProductForm } from '@/components/products/ProductForm';
import { ProductViewModal } from '@/components/products/ProductViewModal';
import { purchaseOrderColumns } from '@/components/purchase-orders/PurchaseOrderColumns';
import { createPurchaseOrderFilterFields } from '@/components/purchase-orders/PurchaseOrderFilters';
import { PurchaseOrderForm } from '@/components/purchase-orders/PurchaseOrderForm';
import { PurchaseOrderViewModal } from '@/components/purchase-orders/PurchaseOrderViewModal';
import { purchaseRequestColumns } from '@/components/purchase-requests/PurchaseRequestColumns';
import { createPurchaseRequestFilterFields } from '@/components/purchase-requests/PurchaseRequestFilters';
import { PurchaseRequestForm } from '@/components/purchase-requests/PurchaseRequestForm';
import { PurchaseRequestViewModal } from '@/components/purchase-requests/PurchaseRequestViewModal';
import { stockAdjustmentColumns } from '@/components/stock-adjustments/StockAdjustmentColumns';
import { createStockAdjustmentFilterFields } from '@/components/stock-adjustments/StockAdjustmentFilters';
import { StockAdjustmentForm } from '@/components/stock-adjustments/StockAdjustmentForm';
import { StockAdjustmentViewModal } from '@/components/stock-adjustments/StockAdjustmentViewModal';
import { stockTransferColumns } from '@/components/stock-transfers/StockTransferColumns';
import { createStockTransferFilterFields } from '@/components/stock-transfers/StockTransferFilters';
import { StockTransferForm } from '@/components/stock-transfers/StockTransferForm';
import { StockTransferViewModal } from '@/components/stock-transfers/StockTransferViewModal';
import { supplierReturnColumns } from '@/components/supplier-returns/SupplierReturnColumns';
import { createSupplierReturnFilterFields } from '@/components/supplier-returns/SupplierReturnFilters';
import { SupplierReturnForm } from '@/components/supplier-returns/SupplierReturnForm';
import { SupplierReturnViewModal } from '@/components/supplier-returns/SupplierReturnViewModal';
import { supplierColumns } from '@/components/suppliers/SupplierColumns';
import { createSupplierFilterFields } from '@/components/suppliers/SupplierFilters';
import { SupplierForm } from '@/components/suppliers/SupplierForm';
import { SupplierViewModal } from '@/components/suppliers/SupplierViewModal';
import { unitColumns, type Unit } from '@/components/units/UnitColumns';
import { UnitForm } from '@/components/units/UnitForm';
import { UnitViewModal } from '@/components/units/UnitViewModal';
import { warehouseColumns } from '@/components/warehouses/WarehouseColumns';
import { createWarehouseFilterFields } from '@/components/warehouses/WarehouseFilters';
import { WarehouseForm } from '@/components/warehouses/WarehouseForm';
import { WarehouseViewModal } from '@/components/warehouses/WarehouseViewModal';
import { type Asset } from '@/types/asset';
import { type AssetCategory } from '@/types/asset-category';
import { type AssetMaintenance } from '@/types/asset-maintenance';
import { type GoodsReceipt } from '@/types/goods-receipt';
import { type InventoryStocktake } from '@/types/inventory-stocktake';
import { type PurchaseOrder } from '@/types/purchase-order';
import { type PurchaseRequest } from '@/types/purchase-request';
import { type StockAdjustment } from '@/types/stock-adjustment';
import { type StockTransfer } from '@/types/stock-transfer';
import { type SupplierReturn } from '@/types/supplier-return';
import { createSimpleEntityColumns } from '@/utils/columns';

// Helper function to create generic delete messages
const createGenericDeleteMessage =
    (entityName: string) => (item: { name?: string }) => {
        const normalizedName = entityName.toLowerCase();
        const subject = item.name || `this ${normalizedName}`;

        return `This action cannot be undone. This will permanently delete ${subject}'s ${normalizedName} record.`;
    };

// Configuration builder options
export interface SimpleEntityConfigOptions<T = unknown> {
    entityName: string;
    entityNamePlural: string;
    description?: string;
    apiBase: string;
    filterPlaceholder: string;
    permissionPrefix?: string;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    viewModalComponent?: React.ComponentType<any>;
    getDeleteMessage?: (item: T) => string;
    defaultSorting?: SortingState;
    titleKey?: string;
    descriptionKey?: string;
}

export interface ComplexEntityConfigOptions<T = Record<string, unknown>> {
    entityName: string;
    entityNamePlural: string;
    description?: string;
    apiEndpoint: string;
    exportEndpoint: string;
    queryKey: string[];
    identifierKey?: string;
    breadcrumbs: BreadcrumbItem[];
    initialFilters: Record<string, string | number | undefined>;
    columns: ColumnDef<T>[];
    filterFields: FieldDescriptor[];
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    formComponent: React.ComponentType<any>;
    formType: FormComponentType;
    entityNameForSearch?: string;
    getDeleteMessage: (item: T) => string;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    viewModalComponent?: React.ComponentType<any>;
    defaultColumnVisibility?: Record<string, boolean>;
    defaultSorting?: SortingState;
    loadDetailOnEdit?: boolean;
    detailLoadErrorMessage?: string;
    permissionPrefix?: string;
    titleKey?: string;
    descriptionKey?: string;
}

// Factory to create a bound SimpleEntityViewModal for a specific entity
function createSimpleEntityViewModal(entityName: string) {
    return function BoundSimpleEntityViewModal(
        props: React.ComponentProps<typeof SimpleEntityViewModal>,
    ) {
        return React.createElement(SimpleEntityViewModal, {
            ...props,
            entityName,
        });
    };
}

// Enhanced helper function to create simple entity configs with consistent structure
function createSimpleEntityConfig<
    T extends {
        id: number;
        name: string;
        created_at: string;
        updated_at: string;
    },
>(
    options: Omit<SimpleEntityConfigOptions, 'viewModalComponent'>,
): CustomEntityConfig<T> {
    const {
        entityName,
        entityNamePlural,
        description,
        apiBase,
        filterPlaceholder,
        permissionPrefix,
        defaultSorting,
        titleKey,
        descriptionKey,
    } = options;

    return {
        entityName,
        entityNamePlural,
        description,
        permissionPrefix,
        defaultSorting,
        titleKey,
        descriptionKey,
        apiEndpoint: `/api/${apiBase}`,
        exportEndpoint: `/api/${apiBase}/export`,
        queryKey: [apiBase],
        breadcrumbs: [{ title: entityNamePlural, href: `/${apiBase}` }],
        initialFilters: { search: '' },
        columns: createSimpleEntityColumns<T>(),
        filterFields: createSimpleEntityFilterFields(filterPlaceholder),
        formComponent: SimpleEntityForm,
        formType: 'simple',
        entityNameForSearch: entityName.toLowerCase(),
        getDeleteMessage: createGenericDeleteMessage(entityName),
        viewModalComponent: createSimpleEntityViewModal(entityName),
    };
}

// Factory function for complex entity configs
function createComplexEntityConfig<T = Record<string, unknown>>(
    options: ComplexEntityConfigOptions<T>,
): CustomEntityConfig<T> {
    return {
        entityName: options.entityName,
        entityNamePlural: options.entityNamePlural,
        description: options.description,
        apiEndpoint: options.apiEndpoint,
        exportEndpoint: options.exportEndpoint,
        queryKey: options.queryKey,
        identifierKey: options.identifierKey,
        breadcrumbs: options.breadcrumbs,
        initialFilters: options.initialFilters,
        columns: options.columns,
        filterFields: options.filterFields,
        formComponent: options.formComponent,
        formType: options.formType,
        entityNameForSearch: options.entityNameForSearch,
        getDeleteMessage: options.getDeleteMessage,
        viewModalComponent: options.viewModalComponent,
        defaultColumnVisibility: options.defaultColumnVisibility,
        defaultSorting: options.defaultSorting,
        loadDetailOnEdit: options.loadDetailOnEdit,
        detailLoadErrorMessage: options.detailLoadErrorMessage,
        permissionPrefix: options.permissionPrefix,
        titleKey: options.titleKey,
        descriptionKey: options.descriptionKey,
    };
}

// Predefined configurations for simple entities
export const departmentConfig = createSimpleEntityConfig({
    entityName: 'Department',
    entityNamePlural: 'Departments',
    description: 'Organizational units used to group employees and assets.',
    titleKey: 'page.departments.title',
    descriptionKey: 'page.departments.description',
    apiBase: 'departments',
    filterPlaceholder: 'Search departments...',
    permissionPrefix: 'department',
});

export const positionConfig = createSimpleEntityConfig({
    entityName: 'Position',
    entityNamePlural: 'Positions',
    description: 'Job titles assignable to employees.',
    titleKey: 'page.positions.title',
    descriptionKey: 'page.positions.description',
    apiBase: 'positions',
    filterPlaceholder: 'Search positions...',
    permissionPrefix: 'position',
    defaultSorting: [{ id: 'created_at', desc: true }],
});

export const branchConfig = createSimpleEntityConfig({
    entityName: 'Branch',
    entityNamePlural: 'Branches',
    description: 'Company branches used across transactions and reports.',
    titleKey: 'page.branches.title',
    descriptionKey: 'page.branches.description',
    apiBase: 'branches',
    filterPlaceholder: 'Search branches...',
    permissionPrefix: 'branch',
});

export const warehouseConfig = createComplexEntityConfig<Warehouse>({
    entityName: 'Warehouse',
    entityNamePlural: 'Warehouses',
    description: 'Storage locations that hold product stock.',
    apiEndpoint: '/api/warehouses',
    exportEndpoint: '/api/warehouses/export',
    queryKey: ['warehouses'],
    titleKey: 'page.warehouses.title',
    descriptionKey: 'page.warehouses.description',
    breadcrumbs: [
        {
            title: 'Warehouses',
            titleKey: 'menu.warehouse',
            href: '/warehouses',
        },
    ],
    initialFilters: { search: '', branch_id: '' },
    columns: warehouseColumns,
    filterFields: createWarehouseFilterFields(),
    formComponent: WarehouseForm,
    formType: 'complex',
    entityNameForSearch: 'warehouse',
    permissionPrefix: 'warehouse',
    viewModalComponent: WarehouseViewModal,
    getDeleteMessage: (item: { name?: string; code?: string }) =>
        `This action cannot be undone. This will permanently delete warehouse ${item.code} (${item.name}).`,
});

// Configuration for complex entities (employees) - using factory for consistency
export const employeeConfig = createComplexEntityConfig({
    entityName: 'Employee',
    entityNamePlural: 'Employees',
    description:
        'Staff records with employment, department, and position history.',
    titleKey: 'page.employees.title',
    descriptionKey: 'page.employees.description',
    apiEndpoint: '/api/employees',
    exportEndpoint: '/api/employees/export',
    queryKey: ['employees'],
    breadcrumbs: [
        {
            title: 'Employee',
            titleKey: 'menu.employee',
            href: '/employees',
        },
    ],
    initialFilters: {
        search: '',
        department_id: '',
        position_id: '',
        branch_id: '',
    },
    columns: employeeColumns,
    filterFields: createEmployeeFilterFields(),
    formComponent: EmployeeForm,
    formType: 'complex',
    entityNameForSearch: 'employee',
    permissionPrefix: 'employee',
    viewModalComponent: EmployeeViewModal,
    getDeleteMessage: (employee: { name?: string }) =>
        `This action cannot be undone. This will permanently delete ${employee.name}'s employee record.`,
});

// Configuration for complex entities (customers) - using factory for consistency
export const customerConfig = createComplexEntityConfig({
    entityName: 'Customer',
    entityNamePlural: 'Customers',
    description: 'Customer master data with billing and contact details.',
    titleKey: 'page.customers.title',
    descriptionKey: 'page.customers.description',
    apiEndpoint: '/api/customers',
    exportEndpoint: '/api/customers/export',
    queryKey: ['customers'],
    breadcrumbs: [{ title: 'Customers', href: '/customers' }],
    initialFilters: {
        search: '',
        branch_id: '',
        category_id: '',
        status: '',
    },
    columns: customerColumns,
    filterFields: createCustomerFilterFields(),
    formComponent: CustomerForm,
    formType: 'complex',
    entityNameForSearch: 'customer',
    permissionPrefix: 'customer',
    viewModalComponent: CustomerViewModal,
    getDeleteMessage: (customer: { name?: string }) =>
        `This action cannot be undone. This will permanently delete ${customer.name}'s customer record.`,
});

// Configuration for complexes entities (suppliers) - using factory for consistency
export const supplierConfig = createComplexEntityConfig({
    entityName: 'Supplier',
    entityNamePlural: 'Suppliers',
    description: 'Vendor master data used by purchasing and payables.',
    titleKey: 'page.suppliers.title',
    descriptionKey: 'page.suppliers.description',
    apiEndpoint: '/api/suppliers',
    exportEndpoint: '/api/suppliers/export',
    queryKey: ['suppliers'],
    breadcrumbs: [{ title: 'Suppliers', href: '/suppliers' }],
    initialFilters: {
        search: '',
        branch_id: '',
        category_id: '',
        status: '',
    },
    columns: supplierColumns,
    filterFields: createSupplierFilterFields(),
    formComponent: SupplierForm,
    formType: 'complex',
    entityNameForSearch: 'supplier',
    permissionPrefix: 'supplier',
    viewModalComponent: SupplierViewModal,
    getDeleteMessage: (supplier: { name?: string }) =>
        `This action cannot be undone. This will permanently delete ${supplier.name}'s supplier record.`,
});

export const stockTransferConfig = createComplexEntityConfig<StockTransfer>({
    entityName: 'Stock Transfer',
    entityNamePlural: 'Stock Transfers',
    description:
        'Move stock between warehouses with approval and receipt tracking.',
    apiEndpoint: '/api/stock-transfers',
    exportEndpoint: '/api/stock-transfers/export',
    queryKey: ['stock-transfers'],
    titleKey: 'page.stock_transfers.title',
    descriptionKey: 'page.stock_transfers.description',
    breadcrumbs: [
        {
            title: 'Stock Transfers',
            titleKey: 'menu.stock_transfer',
            href: '/stock-transfers',
        },
    ],
    initialFilters: {
        search: '',
        from_warehouse_id: '',
        to_warehouse_id: '',
        status: '',
    },
    columns: stockTransferColumns,
    filterFields: createStockTransferFilterFields(),
    formComponent: StockTransferForm,
    formType: 'complex',
    // Issue #360 keputusan 1: item tidak ada di respons list; Edit memuat detail dulu.
    loadDetailOnEdit: true,
    detailLoadErrorMessage:
        'Failed to load stock transfer details. Please try again.',
    entityNameForSearch: 'stock transfer',
    defaultSorting: [{ id: 'created_at', desc: true }],
    viewModalComponent: StockTransferViewModal,
    getDeleteMessage: (transfer: { transfer_number?: string | null }) =>
        `This action cannot be undone. This will cancel stock transfer ${transfer.transfer_number || ''}.`,
    permissionPrefix: 'stock_transfer',
});

export const inventoryStocktakeConfig =
    createComplexEntityConfig<InventoryStocktake>({
        entityName: 'Inventory Stocktake',
        entityNamePlural: 'Inventory Stocktakes',
        description:
            'Physical counts of warehouse stock with variance results.',
        apiEndpoint: '/api/inventory-stocktakes',
        exportEndpoint: '/api/inventory-stocktakes/export',
        queryKey: ['inventory-stocktakes'],
        titleKey: 'page.inventory_stocktakes.title',
        descriptionKey: 'page.inventory_stocktakes.description',
        breadcrumbs: [
            {
                title: 'Inventory Stocktakes',
                titleKey: 'menu.inventory_stocktake',
                href: '/inventory-stocktakes',
            },
        ],
        initialFilters: {
            search: '',
            warehouse_id: '',
            product_category_id: '',
            status: '',
        },
        columns: inventoryStocktakeColumns,
        filterFields: createInventoryStocktakeFilterFields(),
        formComponent: InventoryStocktakeForm,
        formType: 'complex',
        entityNameForSearch: 'inventory stocktake',
        viewModalComponent: InventoryStocktakeViewModal,
        getDeleteMessage: (stocktake: { stocktake_number?: string | null }) =>
            `This action cannot be undone. This will cancel inventory stocktake ${stocktake.stocktake_number || ''}.`,
        permissionPrefix: 'inventory_stocktake',
    });

export const stockAdjustmentConfig = createComplexEntityConfig<StockAdjustment>(
    {
        entityName: 'Stock Adjustment',
        entityNamePlural: 'Stock Adjustments',
        description:
            'Record stock corrections with approval and journal posting.',
        apiEndpoint: '/api/stock-adjustments',
        exportEndpoint: '/api/stock-adjustments/export',
        queryKey: ['stock-adjustments'],
        titleKey: 'page.stock_adjustments.title',
        descriptionKey: 'page.stock_adjustments.description',
        breadcrumbs: [
            {
                title: 'Stock Adjustments',
                titleKey: 'menu.stock_adjustment',
                href: '/stock-adjustments',
            },
        ],
        initialFilters: {
            search: '',
            warehouse_id: '',
            status: '',
            adjustment_type: '',
        },
        columns: stockAdjustmentColumns,
        filterFields: createStockAdjustmentFilterFields(),
        formComponent: StockAdjustmentForm,
        formType: 'complex',
        entityNameForSearch: 'stock adjustment',
        defaultSorting: [{ id: 'created_at', desc: true }],
        viewModalComponent: StockAdjustmentViewModal,
        getDeleteMessage: (adjustment: { adjustment_number?: string | null }) =>
            `This action cannot be undone. This will cancel stock adjustment ${adjustment.adjustment_number || ''}.`,
        permissionPrefix: 'stock_adjustment',
    },
);

export const purchaseRequestConfig = createComplexEntityConfig<PurchaseRequest>(
    {
        entityName: 'Purchase Request',
        entityNamePlural: 'Purchase Requests',
        description:
            'Internal requests for goods before a purchase order is raised.',
        apiEndpoint: '/api/purchase-requests',
        exportEndpoint: '/api/purchase-requests/export',
        queryKey: ['purchase-requests'],
        titleKey: 'page.purchase_requests.title',
        descriptionKey: 'page.purchase_requests.description',
        breadcrumbs: [
            {
                title: 'Purchase Requests',
                titleKey: 'menu.purchase_request',
                href: '/purchase-requests',
            },
        ],
        initialFilters: {
            search: '',
            branch_id: '',
            department_id: '',
            requested_by: '',
            priority: '',
            status: '',
            request_date_from: '',
            request_date_to: '',
        },
        columns: purchaseRequestColumns,
        defaultColumnVisibility: {
            branch: false,
            department: false,
            requester: false,
            required_date: false,
        },
        filterFields: createPurchaseRequestFilterFields(),
        formComponent: PurchaseRequestForm,
        formType: 'complex',
        entityNameForSearch: 'purchase request',
        viewModalComponent: PurchaseRequestViewModal,
        getDeleteMessage: (purchaseRequest: { pr_number?: string | null }) =>
            `This action cannot be undone. This will cancel purchase request ${purchaseRequest.pr_number || ''}.`,
        permissionPrefix: 'purchase_request',
    },
);

export const purchaseOrderConfig = createComplexEntityConfig<PurchaseOrder>({
    entityName: 'Purchase Order',
    entityNamePlural: 'Purchase Orders',
    description: 'Orders sent to suppliers with receipt and billing progress.',
    apiEndpoint: '/api/purchase-orders',
    exportEndpoint: '/api/purchase-orders/export',
    queryKey: ['purchase-orders'],
    titleKey: 'page.purchase_orders.title',
    descriptionKey: 'page.purchase_orders.description',
    breadcrumbs: [
        {
            title: 'Purchase Orders',
            titleKey: 'menu.purchase_order',
            href: '/purchase-orders',
        },
    ],
    initialFilters: {
        search: '',
        supplier_id: '',
        warehouse_id: '',
        status: '',
        currency: '',
        order_date_from: '',
        order_date_to: '',
    },
    columns: purchaseOrderColumns,
    defaultColumnVisibility: {
        warehouse: false,
        currency: false,
        expected_delivery_date: false,
    },
    filterFields: createPurchaseOrderFilterFields(),
    formComponent: PurchaseOrderForm,
    formType: 'complex',
    entityNameForSearch: 'purchase order',
    viewModalComponent: PurchaseOrderViewModal,
    getDeleteMessage: (purchaseOrder: { po_number?: string | null }) =>
        `This action cannot be undone. This will delete purchase order ${purchaseOrder.po_number || ''}.`,
    permissionPrefix: 'purchase_order',
});

export const goodsReceiptConfig = createComplexEntityConfig<GoodsReceipt>({
    entityName: 'Goods Receipt',
    entityNamePlural: 'Goods Receipts',
    description: 'Record received goods and update warehouse stock.',
    apiEndpoint: '/api/goods-receipts',
    exportEndpoint: '/api/goods-receipts/export',
    queryKey: ['goods-receipts'],
    titleKey: 'page.goods_receipts.title',
    descriptionKey: 'page.goods_receipts.description',
    breadcrumbs: [
        {
            title: 'Goods Receipts',
            titleKey: 'menu.goods_receipt',
            href: '/goods-receipts',
        },
    ],
    initialFilters: {
        search: '',
        purchase_order_id: '',
        warehouse_id: '',
        status: '',
        received_by: '',
        receipt_date_from: '',
        receipt_date_to: '',
    },
    columns: goodsReceiptColumns,
    filterFields: createGoodsReceiptFilterFields(),
    formComponent: GoodsReceiptForm,
    formType: 'complex',
    entityNameForSearch: 'goods receipt',
    viewModalComponent: GoodsReceiptViewModal,
    getDeleteMessage: (goodsReceipt: { gr_number?: string | null }) =>
        `This action cannot be undone. This will delete goods receipt ${goodsReceipt.gr_number || ''}.`,
    permissionPrefix: 'goods_receipt',
});

export const supplierReturnConfig = createComplexEntityConfig<SupplierReturn>({
    entityName: 'Supplier Return',
    entityNamePlural: 'Supplier Returns',
    description: 'Return received goods to suppliers with stock deduction.',
    apiEndpoint: '/api/supplier-returns',
    exportEndpoint: '/api/supplier-returns/export',
    queryKey: ['supplier-returns'],
    titleKey: 'page.supplier_returns.title',
    descriptionKey: 'page.supplier_returns.description',
    breadcrumbs: [
        {
            title: 'Supplier Returns',
            titleKey: 'menu.supplier_return',
            href: '/supplier-returns',
        },
    ],
    initialFilters: {
        search: '',
        purchase_order_id: '',
        goods_receipt_id: '',
        supplier_id: '',
        warehouse_id: '',
        reason: '',
        status: '',
        return_date_from: '',
        return_date_to: '',
    },
    columns: supplierReturnColumns,
    defaultColumnVisibility: {
        warehouse: false,
        gr_number: false,
        reason: false,
    },
    filterFields: createSupplierReturnFilterFields(),
    formComponent: SupplierReturnForm,
    formType: 'complex',
    entityNameForSearch: 'supplier return',
    viewModalComponent: SupplierReturnViewModal,
    getDeleteMessage: (supplierReturn: { return_number?: string | null }) =>
        `This action cannot be undone. This will delete supplier return ${supplierReturn.return_number || ''}.`,
    permissionPrefix: 'supplier_return',
});

export const supplierCategoryConfig = createSimpleEntityConfig({
    entityName: 'Supplier Category',
    entityNamePlural: 'Supplier Categories',
    description: 'Categories used to group suppliers.',
    titleKey: 'page.supplier_categories.title',
    descriptionKey: 'page.supplier_categories.description',
    apiBase: 'supplier-categories',
    filterPlaceholder: 'Search supplier categories...',
    permissionPrefix: 'supplier_category',
});

export const customerCategoryConfig = createSimpleEntityConfig({
    entityName: 'Customer Category',
    entityNamePlural: 'Customer Categories',
    description: 'Categories used to group customers.',
    titleKey: 'page.customer_categories.title',
    descriptionKey: 'page.customer_categories.description',
    apiBase: 'customer-categories',
    filterPlaceholder: 'Search customer categories...',
    permissionPrefix: 'customer_category',
});

export const assetCategoryConfig = createComplexEntityConfig<AssetCategory>({
    entityName: 'Asset Category',
    entityNamePlural: 'Asset Categories',
    description: 'Categories used to classify fixed assets.',
    titleKey: 'page.asset_categories.title',
    descriptionKey: 'page.asset_categories.description',
    apiEndpoint: '/api/asset-categories',
    exportEndpoint: '/api/asset-categories/export',
    queryKey: ['asset-categories'],
    breadcrumbs: [{ title: 'Asset Categories', href: '/asset-categories' }],
    initialFilters: { search: '' },
    columns: assetCategoryColumns,
    filterFields: createSimpleEntityFilterFields('Search asset categories...'),
    formComponent: AssetCategoryForm,
    formType: 'complex',
    entityNameForSearch: 'asset category',
    permissionPrefix: 'asset_category',
    viewModalComponent: AssetCategoryViewModal,
    getDeleteMessage: createGenericDeleteMessage('Asset Category'),
});

export const productCategoryConfig = createComplexEntityConfig<ProductCategory>(
    {
        entityName: 'Product Category',
        entityNamePlural: 'Product Categories',
        description: 'Categories used to classify products and materials.',
        titleKey: 'page.product_categories.title',
        descriptionKey: 'page.product_categories.description',
        apiEndpoint: '/api/product-categories',
        exportEndpoint: '/api/product-categories/export',
        queryKey: ['product-categories'],
        breadcrumbs: [
            { title: 'Product Categories', href: '/product-categories' },
        ],
        initialFilters: { search: '' },
        columns: productCategoryColumns,
        filterFields: createSimpleEntityFilterFields(
            'Search product categories...',
        ),
        formComponent: ProductCategoryForm,
        formType: 'complex',
        entityNameForSearch: 'product category',
        permissionPrefix: 'product_category',
        viewModalComponent: ProductCategoryViewModal,
        getDeleteMessage: createGenericDeleteMessage('Product Category'),
    },
);

export const unitConfig = createComplexEntityConfig<Unit>({
    entityName: 'Unit',
    entityNamePlural: 'Units',
    description: 'Units of measure used by products and transactions.',
    titleKey: 'page.units.title',
    descriptionKey: 'page.units.description',
    apiEndpoint: '/api/units',
    exportEndpoint: '/api/units/export',
    queryKey: ['units'],
    breadcrumbs: [{ title: 'Units', href: '/units' }],
    initialFilters: { search: '' },
    columns: unitColumns,
    filterFields: createSimpleEntityFilterFields('Search units...'),
    formComponent: UnitForm,
    formType: 'complex',
    entityNameForSearch: 'unit',
    permissionPrefix: 'unit',
    viewModalComponent: UnitViewModal,
    getDeleteMessage: createGenericDeleteMessage('Unit'),
});

export const productConfig = createComplexEntityConfig({
    entityName: 'Product',
    entityNamePlural: 'Products',
    description:
        'Products, materials, and services with pricing and stock settings.',
    apiEndpoint: '/api/products',
    exportEndpoint: '/api/products/export',
    queryKey: ['products'],
    titleKey: 'menu.product',
    descriptionKey: 'page.products.description',
    breadcrumbs: [
        {
            title: 'Product',
            titleKey: 'menu.product',
            href: '/products',
        },
    ],
    initialFilters: {
        search: '',
        product_category_id: '',
        unit_id: '',
        branch_id: '',
        type: '',
        status: '',
    },
    columns: productColumns,
    filterFields: createProductFilterFields(),
    formComponent: ProductForm,
    formType: 'complex',
    entityNameForSearch: 'product',
    permissionPrefix: 'product',
    viewModalComponent: ProductViewModal,
    getDeleteMessage: (product: { name?: string }) =>
        `This action cannot be undone. This will permanently delete ${product.name}'s product record.`,
});

import { fiscalYearColumns } from '@/components/fiscal-years/FiscalYearColumns';
import { createFiscalYearFilterFields } from '@/components/fiscal-years/FiscalYearFilters';
import { FiscalYearForm } from '@/components/fiscal-years/FiscalYearForm';
import { FiscalYearViewModal } from '@/components/fiscal-years/FiscalYearViewModal';

export const fiscalYearConfig = createComplexEntityConfig<FiscalYear>({
    entityName: 'Fiscal Year',
    entityNamePlural: 'Fiscal Years',
    description:
        'Accounting periods that scope journals and financial reports.',
    apiEndpoint: '/api/fiscal-years',
    permissionPrefix: 'fiscal_year',
    exportEndpoint: '/api/fiscal-years/export',
    queryKey: ['fiscal-years'],
    titleKey: 'menu.fiscal_year',
    descriptionKey: 'page.fiscal_years.description',
    breadcrumbs: [
        {
            title: 'Fiscal Years',
            titleKey: 'menu.fiscal_year',
            href: '/fiscal-years',
        },
    ],
    initialFilters: { search: '', status: '' },
    columns: fiscalYearColumns,
    filterFields: createFiscalYearFilterFields(),
    formComponent: FiscalYearForm,
    formType: 'complex',
    entityNameForSearch: 'fiscal year',
    viewModalComponent: FiscalYearViewModal,
    getDeleteMessage: createGenericDeleteMessage('Fiscal Year'),
});

import { coaVersionColumns } from '@/components/coa-versions/CoaVersionColumns';
import { createCoaVersionFilterFields } from '@/components/coa-versions/CoaVersionFilters';
import { CoaVersionForm } from '@/components/coa-versions/CoaVersionForm';
import { CoaVersionViewModal } from '@/components/coa-versions/CoaVersionViewModal';
import { type CoaVersion } from '@/types/coa-version';

export const coaVersionConfig = createComplexEntityConfig<CoaVersion>({
    entityName: 'COA Version',
    entityNamePlural: 'COA Versions',
    description: 'Chart of accounts versions per fiscal year.',
    apiEndpoint: '/api/coa-versions',
    permissionPrefix: 'coa_version',
    exportEndpoint: '/api/coa-versions/export',
    queryKey: ['coa-versions'],
    titleKey: 'menu.coa_version',
    descriptionKey: 'page.coa_versions.description',
    breadcrumbs: [
        {
            title: 'COA Versions',
            titleKey: 'menu.coa_version',
            href: '/coa-versions',
        },
    ],
    initialFilters: { search: '', status: '', fiscal_year_id: '' },
    columns: coaVersionColumns,
    filterFields: createCoaVersionFilterFields(),
    formComponent: CoaVersionForm,
    formType: 'complex',
    entityNameForSearch: 'coa version',
    viewModalComponent: CoaVersionViewModal,
    getDeleteMessage: createGenericDeleteMessage('COA Version'),
});

import { accountMappingColumns } from '@/components/account-mappings/AccountMappingColumns';
import { createAccountMappingFilterFields } from '@/components/account-mappings/AccountMappingFilters';
import { AccountMappingForm } from '@/components/account-mappings/AccountMappingForm';
import { AccountMappingViewModal } from '@/components/account-mappings/AccountMappingViewModal';
import { type AccountMapping } from '@/types/account-mapping';

export const accountMappingConfig = createComplexEntityConfig<AccountMapping>({
    entityName: 'Account Mapping',
    entityNamePlural: 'Account Mappings',
    description:
        'Pemetaan akun antarversi COA (rename, merge, split) untuk migrasi saldo dan referensi.',
    apiEndpoint: '/api/account-mappings',
    permissionPrefix: 'account_mapping',
    exportEndpoint: '/api/account-mappings/export',
    queryKey: ['account-mappings'],
    titleKey: 'menu.account_mapping',
    descriptionKey: 'page.account_mappings.description',
    breadcrumbs: [
        {
            title: 'Account Mappings',
            titleKey: 'menu.account_mapping',
            href: '/account-mappings',
        },
    ],
    initialFilters: {
        search: '',
        type: '',
        source_coa_version_id: '',
        target_coa_version_id: '',
    },
    columns: accountMappingColumns,
    defaultColumnVisibility: {
        source_account_id: false,
        target_account_id: false,
        created_at: false,
    },
    filterFields: createAccountMappingFilterFields(),
    formComponent: AccountMappingForm,
    formType: 'complex',
    entityNameForSearch: 'account mapping',
    viewModalComponent: AccountMappingViewModal,
    getDeleteMessage: () =>
        'This action cannot be undone. This will permanently delete this account mapping.',
});

import { journalEntryColumns } from '@/components/journal-entries/JournalEntryColumns';
import { createJournalEntryFilterFields } from '@/components/journal-entries/JournalEntryFilters';
import { JournalEntryForm } from '@/components/journal-entries/JournalEntryForm';
import { JournalEntryViewModal } from '@/components/journal-entries/JournalEntryViewModal';
import { type JournalEntry } from '@/types/journal-entry';

export const journalEntryConfig = createComplexEntityConfig<JournalEntry>({
    entityName: 'Journal Entry',
    entityNamePlural: 'Journal Entries',
    description: 'Manual journal entries with debit and credit lines.',
    apiEndpoint: '/api/journal-entries',
    permissionPrefix: 'journal_entry',
    exportEndpoint: '/api/journal-entries/export',
    queryKey: ['journal-entries'],
    titleKey: 'page.journal_entries.title',
    descriptionKey: 'page.journal_entries.description',
    breadcrumbs: [
        {
            title: 'Journal Entries',
            titleKey: 'page.journal_entries.title',
            href: '/journal-entries',
        },
    ],
    initialFilters: { search: '', status: '', start_date: '', end_date: '' },
    columns: journalEntryColumns,
    filterFields: createJournalEntryFilterFields(),
    formComponent: JournalEntryForm,
    formType: 'complex',
    entityNameForSearch: 'journal entry',
    viewModalComponent: JournalEntryViewModal,
    defaultColumnVisibility: { reference: false, total_debit: false },
    getDeleteMessage: (item: JournalEntry) =>
        `This action cannot be undone. This will permanently delete Journal Entry ${item.entry_number}.`,
});

import { assetModelColumns } from '@/components/asset-models/AssetModelColumns';
import { createAssetModelFilterFields } from '@/components/asset-models/AssetModelFilters';
import { AssetModelForm } from '@/components/asset-models/AssetModelForm';
import { AssetModelViewModal } from '@/components/asset-models/AssetModelViewModal';
import { type AssetModel } from '@/types/asset-model';

export const assetModelConfig = createComplexEntityConfig<AssetModel>({
    entityName: 'Asset Model',
    entityNamePlural: 'Asset Models',
    description: 'Manufacturer models used as templates for assets.',
    titleKey: 'page.asset_models.title',
    descriptionKey: 'page.asset_models.description',
    apiEndpoint: '/api/asset-models',
    exportEndpoint: '/api/asset-models/export',
    queryKey: ['asset-models'],
    breadcrumbs: [{ title: 'Asset Models', href: '/asset-models' }],
    initialFilters: { search: '', asset_category_id: '' },
    columns: assetModelColumns,
    filterFields: createAssetModelFilterFields(),
    formComponent: AssetModelForm,
    formType: 'complex',
    entityNameForSearch: 'asset model',
    viewModalComponent: AssetModelViewModal,
    getDeleteMessage: (item: { model_name?: string }) =>
        `This action cannot be undone. This will permanently delete ${item.model_name || 'this asset model'}.`,
    permissionPrefix: 'asset_model',
});

import { assetLocationColumns } from '@/components/asset-locations/AssetLocationColumns';
import { createAssetLocationFilterFields } from '@/components/asset-locations/AssetLocationFilters';
import { AssetLocationForm } from '@/components/asset-locations/AssetLocationForm';
import { AssetLocationViewModal } from '@/components/asset-locations/AssetLocationViewModal';
import { type AssetLocation } from '@/types/asset-location';

export const assetLocationConfig = createComplexEntityConfig<AssetLocation>({
    entityName: 'Asset Location',
    entityNamePlural: 'Asset Locations',
    description: 'Physical locations within branches for asset placement.',
    titleKey: 'page.asset_locations.title',
    descriptionKey: 'page.asset_locations.description',
    apiEndpoint: '/api/asset-locations',
    exportEndpoint: '/api/asset-locations/export',
    queryKey: ['asset-locations'],
    breadcrumbs: [{ title: 'Asset Locations', href: '/asset-locations' }],
    initialFilters: { search: '', branch_id: '', parent_id: '' },
    columns: assetLocationColumns,
    filterFields: createAssetLocationFilterFields(),
    formComponent: AssetLocationForm,
    formType: 'complex',
    entityNameForSearch: 'asset location',
    viewModalComponent: AssetLocationViewModal,
    getDeleteMessage: (item: { name?: string }) =>
        `This action cannot be undone. This will permanently delete ${item.name || 'this asset location'}.`,
    permissionPrefix: 'asset_location',
});

export const assetConfig = createComplexEntityConfig<Asset>({
    entityName: 'Asset',
    entityNamePlural: 'Assets',
    description:
        'Fixed asset register with cost, depreciation, and assignment.',
    titleKey: 'page.assets.title',
    descriptionKey: 'page.assets.description',
    apiEndpoint: '/api/assets',
    exportEndpoint: '/api/assets/export',
    queryKey: ['assets'],
    identifierKey: 'ulid',
    breadcrumbs: [
        {
            title: 'Asset Data',
            titleKey: 'menu.asset_data',
            href: '/assets',
        },
    ],
    initialFilters: {
        search: '',
        asset_category_id: '',
        asset_location_id: '',
        department_id: '',
        employee_id: '',
        supplier_id: '',
        branch_id: '',
        status: '',
        condition: '',
    },
    columns: assetColumns,
    filterFields: createAssetFilterFields(),
    formComponent: AssetForm,
    formType: 'complex',
    entityNameForSearch: 'asset',
    viewModalComponent: AssetViewModal,
    getDeleteMessage: (item: { name?: string; asset_code?: string }) =>
        `This action cannot be undone. This will permanently delete asset ${item.asset_code} (${item.name}).`,
    permissionPrefix: 'asset',
});

export const assetMovementConfig = createComplexEntityConfig({
    entityName: 'Asset Movement',
    entityNamePlural: 'Asset Movements',
    description: 'Transfer and assignment history of fixed assets.',
    titleKey: 'page.asset_movements.title',
    descriptionKey: 'page.asset_movements.description',
    apiEndpoint: '/api/asset-movements',
    exportEndpoint: '/api/asset-movements/export',
    queryKey: ['asset-movements'],
    breadcrumbs: [{ title: 'Asset Movements', href: '/asset-movements' }],
    initialFilters: {
        search: '',
        asset_id: '',
        movement_type: '',
    },
    columns: assetMovementColumns,
    filterFields: createAssetMovementFilterFields(),
    formComponent: AssetMovementForm,
    formType: 'complex',
    entityNameForSearch: 'movement',
    viewModalComponent: AssetMovementViewModal,
    getDeleteMessage: () =>
        'This action cannot be undone. This will permanently delete this movement record.',
    permissionPrefix: 'asset_movement',
});

export const assetMaintenanceConfig =
    createComplexEntityConfig<AssetMaintenance>({
        entityName: 'Asset Maintenance',
        entityNamePlural: 'Asset Maintenances',
        description: 'Scheduled and performed maintenance for fixed assets.',
        titleKey: 'page.asset_maintenances.title',
        descriptionKey: 'page.asset_maintenances.description',
        apiEndpoint: '/api/asset-maintenances',
        exportEndpoint: '/api/asset-maintenances/export',
        queryKey: ['asset-maintenances'],
        breadcrumbs: [
            { title: 'Asset Maintenances', href: '/asset-maintenances' },
        ],
        initialFilters: {
            search: '',
            asset_id: '',
            maintenance_type: '',
            status: '',
            supplier_id: '',
            scheduled_from: '',
            scheduled_to: '',
            performed_from: '',
            performed_to: '',
            cost_min: '',
            cost_max: '',
        },
        columns: assetMaintenanceColumns,
        filterFields: createAssetMaintenanceFilterFields(),
        formComponent: AssetMaintenanceForm,
        formType: 'complex',
        entityNameForSearch: 'maintenance',
        viewModalComponent: AssetMaintenanceViewModal,
        defaultColumnVisibility: {
            supplier: false,
            performed_at: false,
            maintenance_type: false,
        },
        getDeleteMessage: () =>
            'This action cannot be undone. This will permanently delete this maintenance record.',
        permissionPrefix: 'asset_maintenance',
    });

import { assetStocktakeColumns } from '@/components/asset-stocktakes/AssetStocktakeColumns';
import { createAssetStocktakeFilterFields } from '@/components/asset-stocktakes/AssetStocktakeFilters';
import { AssetStocktakeForm } from '@/components/asset-stocktakes/AssetStocktakeForm';
import { AssetStocktakeViewModal } from '@/components/asset-stocktakes/AssetStocktakeViewModal';
import { type AssetStocktake } from '@/types/asset-stocktake';

export const assetStocktakeConfig = createComplexEntityConfig<AssetStocktake>({
    entityName: 'Asset Stocktake',
    entityNamePlural: 'Asset Stocktakes',
    description: 'Physical verification of fixed assets per branch.',
    titleKey: 'page.asset_stocktakes.title',
    descriptionKey: 'page.asset_stocktakes.description',
    apiEndpoint: '/api/asset-stocktakes',
    exportEndpoint: '/api/asset-stocktakes/export',
    queryKey: ['asset-stocktakes'],
    identifierKey: 'ulid',
    breadcrumbs: [{ title: 'Asset Stocktakes', href: '/asset-stocktakes' }],
    initialFilters: {
        search: '',
        branch_id: '',
        status: '',
        planned_at_from: '',
        planned_at_to: '',
    },
    columns: assetStocktakeColumns,
    filterFields: createAssetStocktakeFilterFields(),
    formComponent: AssetStocktakeForm,
    formType: 'complex',
    entityNameForSearch: 'stocktake',
    viewModalComponent: AssetStocktakeViewModal,
    getDeleteMessage: (item: { reference?: string }) =>
        `This action cannot be undone. This will permanently delete stocktake ${item.reference}.`,
    permissionPrefix: 'asset_stocktake',
});

import { pipelineColumns } from '@/components/pipelines/PipelineColumns';
import { createPipelineFilterFields } from '@/components/pipelines/PipelineFilters';
import { PipelineForm } from '@/components/pipelines/PipelineForm';
import { PipelineViewModal } from '@/components/pipelines/PipelineViewModal';

export const pipelineConfig = createComplexEntityConfig<Pipeline>({
    entityName: 'Pipeline',
    entityNamePlural: 'Pipelines',
    description: 'Workflow pipelines and the states entities move through.',
    apiEndpoint: '/api/pipelines',
    permissionPrefix: 'pipeline',
    exportEndpoint: '/api/pipelines/export',
    queryKey: ['pipelines'],
    breadcrumbs: [{ title: 'Pipelines', href: '/pipelines' }],
    initialFilters: { search: '', entity_type: '', is_active: '' },
    columns: pipelineColumns,
    filterFields: createPipelineFilterFields(),
    formComponent: PipelineForm,
    formType: 'complex',
    entityNameForSearch: 'pipeline',
    viewModalComponent: PipelineViewModal,
    getDeleteMessage: (item: { name?: string }) =>
        `This action cannot be undone. This will permanently delete the pipeline "${item.name}".`,
});

import { approvalFlowColumns } from '@/components/approval-flows/ApprovalFlowColumns';
import { createApprovalFlowFilterFields } from '@/components/approval-flows/ApprovalFlowFilters';
import { ApprovalFlowForm } from '@/components/approval-flows/ApprovalFlowForm';
import { ApprovalFlowViewModal } from '@/components/approval-flows/ApprovalFlowViewModal';

import { type ApprovalFlow } from '@/types/approval';

export const approvalFlowConfig = createComplexEntityConfig<ApprovalFlow>({
    entityName: 'Approval Flow',
    entityNamePlural: 'Approval Flows',
    description: 'Approval definitions with ordered steps and approvers.',
    apiEndpoint: '/api/approval-flows',
    permissionPrefix: 'approval_flow',
    exportEndpoint: '/api/approval-flows/export',
    queryKey: ['approval-flows'],
    breadcrumbs: [{ title: 'Approval Flows', href: '/approval-flows' }],
    initialFilters: { search: '', approvable_type: '', is_active: '' },
    columns: approvalFlowColumns,
    filterFields: createApprovalFlowFilterFields(),
    formComponent: ApprovalFlowForm,
    formType: 'complex',
    entityNameForSearch: 'approval flow',
    viewModalComponent: ApprovalFlowViewModal,
    getDeleteMessage: (item: { name?: string }) =>
        `This action cannot be undone. This will permanently delete the approval flow "${item.name}".`,
});

import { apPaymentColumns } from '@/components/ap-payments/ApPaymentColumns';
import { createApPaymentFilterFields } from '@/components/ap-payments/ApPaymentFilters';
import { ApPaymentForm } from '@/components/ap-payments/ApPaymentForm';
import { ApPaymentViewModal } from '@/components/ap-payments/ApPaymentViewModal';
import { approvalDelegationColumns } from '@/components/approval-delegations/ApprovalDelegationColumns';
import { createApprovalDelegationFilterFields } from '@/components/approval-delegations/ApprovalDelegationFilters';
import { ApprovalDelegationForm } from '@/components/approval-delegations/ApprovalDelegationForm';
import { ApprovalDelegationViewModal } from '@/components/approval-delegations/ApprovalDelegationViewModal';
import { supplierBillColumns } from '@/components/supplier-bills/SupplierBillColumns';
import { createSupplierBillFilterFields } from '@/components/supplier-bills/SupplierBillFilters';
import { SupplierBillForm } from '@/components/supplier-bills/SupplierBillForm';
import { SupplierBillViewModal } from '@/components/supplier-bills/SupplierBillViewModal';

import { type ApPayment } from '@/types/ap-payment';
import { type ApprovalDelegation } from '@/types/approval-delegation';
import { type SupplierBill } from '@/types/supplier-bill';

export const supplierBillConfig = createComplexEntityConfig<SupplierBill>({
    entityName: 'Supplier Bill',
    entityNamePlural: 'Supplier Bills',
    description: 'Supplier invoices that create accounts payable.',
    apiEndpoint: '/api/supplier-bills',
    exportEndpoint: '/api/supplier-bills/export',
    queryKey: ['supplier-bills'],
    titleKey: 'menu.supplier_bill',
    descriptionKey: 'page.supplier_bills.description',
    breadcrumbs: [
        {
            title: 'Supplier Bills',
            titleKey: 'menu.supplier_bill',
            href: '/supplier-bills',
        },
    ],
    initialFilters: {
        search: '',
        supplier_id: '',
        branch_id: '',
        status: '',
        currency: '',
        bill_date_from: '',
        bill_date_to: '',
        due_date_from: '',
        due_date_to: '',
    },
    columns: supplierBillColumns,
    defaultColumnVisibility: {
        branch: false,
        currency: false,
        bill_date: false,
    },
    filterFields: createSupplierBillFilterFields(),
    formComponent: SupplierBillForm,
    formType: 'complex',
    entityNameForSearch: 'supplier bill',
    viewModalComponent: SupplierBillViewModal,
    getDeleteMessage: (bill: { bill_number?: string | null }) =>
        `This action cannot be undone. This will delete supplier bill ${bill.bill_number || ''}.`,
    permissionPrefix: 'supplier_bill',
});

export const apPaymentConfig = createComplexEntityConfig<ApPayment>({
    entityName: 'AP Payment',
    entityNamePlural: 'AP Payments',
    description: 'Payments to suppliers allocated against outstanding bills.',
    apiEndpoint: '/api/ap-payments',
    exportEndpoint: '/api/ap-payments/export',
    queryKey: ['ap-payments'],
    loadDetailOnEdit: true,
    detailLoadErrorMessage:
        'Failed to load AP payment details. Please try again.',
    titleKey: 'menu.ap_payment',
    descriptionKey: 'page.ap_payments.description',
    breadcrumbs: [
        {
            title: 'AP Payments',
            titleKey: 'menu.ap_payment',
            href: '/ap-payments',
        },
    ],
    initialFilters: {
        search: '',
        supplier_id: '',
        branch_id: '',
        status: '',
        payment_method: '',
        currency: '',
        payment_date_from: '',
        payment_date_to: '',
    },
    columns: apPaymentColumns,
    defaultColumnVisibility: {
        branch: false,
        currency: false,
        payment_method: false,
        total_unallocated: false,
    },
    filterFields: createApPaymentFilterFields(),
    formComponent: ApPaymentForm,
    formType: 'complex',
    entityNameForSearch: 'AP payment',
    viewModalComponent: ApPaymentViewModal,
    getDeleteMessage: (payment: { payment_number?: string | null }) =>
        `This action cannot be undone. This will delete AP payment ${payment.payment_number || ''}.`,
    permissionPrefix: 'ap_payment',
});

export const approvalDelegationConfig =
    createComplexEntityConfig<ApprovalDelegation>({
        entityName: 'Approval Delegation',
        entityNamePlural: 'Approval Delegations',
        description:
            'Delegations that let another user approve on your behalf for a period.',
        apiEndpoint: '/api/approval-delegations',
        permissionPrefix: 'approval_delegation',
        exportEndpoint: '/api/approval-delegations/export',
        queryKey: ['approval-delegations'],
        breadcrumbs: [
            { title: 'Approval Delegations', href: '/approval-delegations' },
        ],
        initialFilters: {
            search: '',
            delegator_user_id: '',
            delegate_user_id: '',
            is_active: '',
            start_date_from: '',
            start_date_to: '',
        },
        columns: approvalDelegationColumns,
        filterFields: createApprovalDelegationFilterFields(),
        formComponent: ApprovalDelegationForm,
        formType: 'complex',
        entityNameForSearch: 'approval delegation',
        viewModalComponent: ApprovalDelegationViewModal,
        getDeleteMessage: () =>
            `This action cannot be undone. This will permanently delete the selected approval delegation.`,
    });

// Import AR module components
import { customerInvoiceColumns } from '@/components/customer-invoices/CustomerInvoiceColumns';
import { createCustomerInvoiceFilterFields } from '@/components/customer-invoices/CustomerInvoiceFilters';
import { CustomerInvoiceForm } from '@/components/customer-invoices/CustomerInvoiceForm';
import { CustomerInvoiceViewModal } from '@/components/customer-invoices/CustomerInvoiceViewModal';
import { type CustomerInvoice } from '@/types/customer-invoice';

import { arReceiptColumns } from '@/components/ar-receipts/ArReceiptColumns';
import { createArReceiptFilterFields } from '@/components/ar-receipts/ArReceiptFilters';
import { ArReceiptForm } from '@/components/ar-receipts/ArReceiptForm';
import { ArReceiptViewModal } from '@/components/ar-receipts/ArReceiptViewModal';
import { type ArReceipt } from '@/types/ar-receipt';

import { creditNoteColumns } from '@/components/credit-notes/CreditNoteColumns';
import { createCreditNoteFilterFields } from '@/components/credit-notes/CreditNoteFilters';
import { CreditNoteForm } from '@/components/credit-notes/CreditNoteForm';
import { CreditNoteViewModal } from '@/components/credit-notes/CreditNoteViewModal';
import { type CreditNote } from '@/types/credit-note';

export const customerInvoiceConfig = createComplexEntityConfig<CustomerInvoice>(
    {
        entityName: 'Customer Invoice',
        entityNamePlural: 'Customer Invoices',
        description: 'Customer invoices that create accounts receivable.',
        apiEndpoint: '/api/customer-invoices',
        exportEndpoint: '/api/customer-invoices/export',
        queryKey: ['customer-invoices'],
        titleKey: 'menu.customer_invoice',
        descriptionKey: 'page.customer_invoices.description',
        breadcrumbs: [
            {
                title: 'Customer Invoices',
                titleKey: 'menu.customer_invoice',
                href: '/customer-invoices',
            },
        ],
        initialFilters: {
            search: '',
            customer_id: '',
            branch_id: '',
            status: '',
            currency: '',
            invoice_date_from: '',
            invoice_date_to: '',
            due_date_from: '',
            due_date_to: '',
        },
        columns: customerInvoiceColumns,
        defaultColumnVisibility: {
            branch: false,
            currency: false,
            invoice_date: false,
        },
        filterFields: createCustomerInvoiceFilterFields(),
        formComponent: CustomerInvoiceForm,
        formType: 'complex',
        entityNameForSearch: 'customer invoice',
        viewModalComponent: CustomerInvoiceViewModal,
        getDeleteMessage: (item: { invoice_number?: string | null }) =>
            `This action cannot be undone. This will permanently delete invoice ${item.invoice_number}.`,
        permissionPrefix: 'customer_invoice',
    },
);

export const arReceiptConfig = createComplexEntityConfig<ArReceipt>({
    entityName: 'AR Receipt',
    entityNamePlural: 'AR Receipts',
    description: 'Customer payments allocated against outstanding invoices.',
    apiEndpoint: '/api/ar-receipts',
    exportEndpoint: '/api/ar-receipts/export',
    queryKey: ['ar-receipts'],
    loadDetailOnEdit: true,
    detailLoadErrorMessage:
        'Failed to load AR receipt details. Please try again.',
    titleKey: 'menu.ar_receipt',
    descriptionKey: 'page.ar_receipts.description',
    breadcrumbs: [
        {
            title: 'AR Receipts',
            titleKey: 'menu.ar_receipt',
            href: '/ar-receipts',
        },
    ],
    initialFilters: {
        search: '',
        customer_id: '',
        branch_id: '',
        status: '',
        payment_method: '',
        currency: '',
        receipt_date_from: '',
        receipt_date_to: '',
    },
    columns: arReceiptColumns,
    defaultColumnVisibility: {
        branch: false,
        currency: false,
        payment_method: false,
        total_unallocated: false,
    },
    filterFields: createArReceiptFilterFields(),
    formComponent: ArReceiptForm,
    formType: 'complex',
    entityNameForSearch: 'ar receipt',
    viewModalComponent: ArReceiptViewModal,
    getDeleteMessage: (item: { receipt_number?: string | null }) =>
        `This action cannot be undone. This will permanently delete receipt ${item.receipt_number}.`,
    permissionPrefix: 'ar_receipt',
});

export const creditNoteConfig = createComplexEntityConfig<CreditNote>({
    entityName: 'Credit Note',
    entityNamePlural: 'Credit Notes',
    description: 'Credit notes that reduce customer invoice balances.',
    apiEndpoint: '/api/credit-notes',
    permissionPrefix: 'credit_note',
    exportEndpoint: '/api/credit-notes/export',
    queryKey: ['credit-notes'],
    titleKey: 'menu.credit_note',
    descriptionKey: 'page.credit_notes.description',
    breadcrumbs: [
        {
            title: 'Credit Notes',
            titleKey: 'menu.credit_note',
            href: '/credit-notes',
        },
    ],
    initialFilters: {
        search: '',
        customer_id: '',
        branch_id: '',
        reason: '',
        status: '',
        credit_note_date_from: '',
        credit_note_date_to: '',
    },
    columns: creditNoteColumns,
    defaultColumnVisibility: {
        reason: false,
    },
    filterFields: createCreditNoteFilterFields(),
    formComponent: CreditNoteForm,
    formType: 'complex',
    entityNameForSearch: 'credit note',
    viewModalComponent: CreditNoteViewModal,
    getDeleteMessage: (item: { credit_note_number?: string | null }) =>
        `This action cannot be undone. This will permanently delete credit note ${item.credit_note_number}.`,
});

import { budgetColumns } from '@/components/budgets/BudgetColumns';
import { createBudgetFilterFields } from '@/components/budgets/BudgetFilters';
import { BudgetForm } from '@/components/budgets/BudgetForm';
import { BudgetViewModal } from '@/components/budgets/BudgetViewModal';
import { recurringJournalColumns } from '@/components/recurring-journals/RecurringJournalColumns';
import { createRecurringJournalFilterFields } from '@/components/recurring-journals/RecurringJournalFilters';
import { RecurringJournalForm } from '@/components/recurring-journals/RecurringJournalForm';
import { RecurringJournalViewModal } from '@/components/recurring-journals/RecurringJournalViewModal';
import { type Budget } from '@/types/budget';
import { type RecurringJournal } from '@/types/recurring-journal';

export const recurringJournalConfig =
    createComplexEntityConfig<RecurringJournal>({
        entityName: 'Recurring Journal',
        entityNamePlural: 'Recurring Journals',
        description: 'Templates for journals that repeat on a schedule.',
        apiEndpoint: '/api/recurring-journals',
        permissionPrefix: 'recurring_journal',
        exportEndpoint: '/api/recurring-journals/export',
        queryKey: ['recurring-journals'],
        titleKey: 'menu.recurring_journal',
        descriptionKey: 'page.recurring_journals.description',
        breadcrumbs: [
            {
                title: 'Recurring Journals',
                titleKey: 'menu.recurring_journal',
                href: '/recurring-journals',
            },
        ],
        initialFilters: { search: '', frequency: '', is_active: '' },
        columns: recurringJournalColumns,
        defaultColumnVisibility: {
            created_at: false,
        },
        filterFields: createRecurringJournalFilterFields(),
        formComponent: RecurringJournalForm,
        formType: 'complex',
        entityNameForSearch: 'recurring journal',
        viewModalComponent: RecurringJournalViewModal,
        getDeleteMessage: (item: { name?: string }) =>
            `This action cannot be undone. This will permanently delete recurring journal ${item.name}.`,
    });

import { bankReconciliationColumns } from '@/components/bank-reconciliations/BankReconciliationColumns';
import { createBankReconciliationFilterFields } from '@/components/bank-reconciliations/BankReconciliationFilters';
import { BankReconciliationForm } from '@/components/bank-reconciliations/BankReconciliationForm';
import { BankReconciliationViewModal } from '@/components/bank-reconciliations/BankReconciliationViewModal';
import { type BankReconciliation } from '@/types/bank-reconciliation';

export const bankReconciliationConfig =
    createComplexEntityConfig<BankReconciliation>({
        entityName: 'Bank Reconciliation',
        entityNamePlural: 'Bank Reconciliations',
        description:
            'Match bank statement balances with the books for each account.',
        apiEndpoint: '/api/bank-reconciliations',
        permissionPrefix: 'bank_reconciliation',
        exportEndpoint: '/api/bank-reconciliations/export',
        queryKey: ['bank-reconciliations'],
        titleKey: 'menu.bank_reconciliation',
        descriptionKey: 'page.bank_reconciliations.description',
        breadcrumbs: [
            {
                title: 'Bank Reconciliations',
                titleKey: 'menu.bank_reconciliation',
                href: '/bank-reconciliations',
            },
        ],
        initialFilters: { search: '', status: '', account_id: '' },
        columns: bankReconciliationColumns,
        filterFields: createBankReconciliationFilterFields(),
        formComponent: BankReconciliationForm,
        formType: 'complex',
        entityNameForSearch: 'bank reconciliation',
        viewModalComponent: BankReconciliationViewModal,
        getDeleteMessage: () =>
            'This action cannot be undone. This will permanently delete this bank reconciliation.',
    });

import { periodClosingColumns } from '@/components/period-closings/PeriodClosingColumns';
import { createPeriodClosingFilterFields } from '@/components/period-closings/PeriodClosingFilters';
import { PeriodClosingForm } from '@/components/period-closings/PeriodClosingForm';
import { PeriodClosingViewModal } from '@/components/period-closings/PeriodClosingViewModal';
import { type PeriodClosing } from '@/types/period-closing';

export const periodClosingConfig = createComplexEntityConfig<PeriodClosing>({
    entityName: 'Period Closing',
    entityNamePlural: 'Period Closings',
    description: 'Close accounting periods and carry balances forward.',
    apiEndpoint: '/api/period-closings',
    permissionPrefix: 'period_closing',
    exportEndpoint: '/api/period-closings/export',
    queryKey: ['period-closings'],
    titleKey: 'menu.period_closing',
    descriptionKey: 'page.period_closings.description',
    breadcrumbs: [
        {
            title: 'Period Closings',
            titleKey: 'menu.period_closing',
            href: '/period-closings',
        },
    ],
    initialFilters: {
        search: '',
        status: '',
        closing_type: '',
        fiscal_year_id: '',
    },
    columns: periodClosingColumns,
    filterFields: createPeriodClosingFilterFields(),
    formComponent: PeriodClosingForm,
    formType: 'complex',
    entityNameForSearch: 'period closing',
    viewModalComponent: PeriodClosingViewModal,
    getDeleteMessage: () =>
        'This action cannot be undone. This will permanently delete this period closing.',
});

import { reportConfigurationColumns } from '@/components/report-configurations/ReportConfigurationColumns';
import { createReportConfigurationFilterFields } from '@/components/report-configurations/ReportConfigurationFilters';
import { ReportConfigurationForm } from '@/components/report-configurations/ReportConfigurationForm';
import { ReportConfigurationViewModal } from '@/components/report-configurations/ReportConfigurationViewModal';
import { type ReportConfiguration } from '@/types/report-configuration';

export const reportConfigurationConfig =
    createComplexEntityConfig<ReportConfiguration>({
        entityName: 'Report Configuration',
        entityNamePlural: 'Report Configurations',
        description:
            'Settings that define how each report is generated and displayed.',
        apiEndpoint: '/api/report-configurations',
        permissionPrefix: 'report_configuration',
        exportEndpoint: '/api/report-configurations/export',
        queryKey: ['report-configurations'],
        breadcrumbs: [
            { title: 'Report Configurations', href: '/report-configurations' },
        ],
        initialFilters: { search: '', report_type: '', is_active: '' },
        columns: reportConfigurationColumns,
        filterFields: createReportConfigurationFilterFields(),
        formComponent: ReportConfigurationForm,
        formType: 'complex',
        entityNameForSearch: 'report configuration',
        viewModalComponent: ReportConfigurationViewModal,
        getDeleteMessage: (item: { name?: string }) =>
            `This action cannot be undone. This will permanently delete report configuration ${item.name}.`,
    });

export const budgetConfig = createComplexEntityConfig<Budget>({
    entityName: 'Budget',
    entityNamePlural: 'Budgets',
    description: 'Budget plans per account with period breakdown.',
    apiEndpoint: '/api/budgets',
    permissionPrefix: 'budget',
    exportEndpoint: '/api/budgets/export',
    queryKey: ['budgets'],
    titleKey: 'menu.budget',
    descriptionKey: 'page.budgets.description',
    breadcrumbs: [
        { title: 'Budgets', titleKey: 'menu.budget', href: '/budgets' },
    ],
    initialFilters: {
        search: '',
        status: '',
        fiscal_year_id: '',
        budget_type: '',
    },
    columns: budgetColumns,
    filterFields: createBudgetFilterFields(),
    formComponent: BudgetForm,
    formType: 'complex',
    entityNameForSearch: 'budget',
    viewModalComponent: BudgetViewModal,
    getDeleteMessage: (item: { name?: string }) =>
        `This action cannot be undone. This will permanently delete budget "${item.name}".`,
});
