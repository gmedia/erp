import { type FormComponentType } from '@/components/common/EntityCrudPage';
import { type FieldDescriptor } from '@/components/common/filters';
import { type FilterState } from '@/hooks/useCrudFilters';
import { type BreadcrumbItem } from '@/types';
import { type EntityWithId, type Warehouse } from '@/types/entity';
import { type ColumnDef } from '@tanstack/react-table';
import * as React from 'react';

// Base configuration interface for all entities with improved typing
export interface BaseEntityConfig<
    FilterType extends FilterState = FilterState,
> {
    readonly entityName: string;
    readonly entityNamePlural: string;
    readonly apiEndpoint: string;
    readonly exportEndpoint: string;
    readonly queryKey: readonly string[];
    readonly identifierKey?: string;
    readonly breadcrumbs: readonly BreadcrumbItem[];
    readonly getDeleteMessage: (item: Record<string, unknown>) => string;
    readonly initialFilters?: FilterType;
}

// Configuration for entities with custom components and stricter typing
export interface CustomEntityConfig<
    T = Record<string, unknown>,
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    FormData = Record<string, unknown>,
    FilterType extends FilterState = FilterState,
> extends BaseEntityConfig<FilterType> {
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
}

// Union type for all entity configurations with improved typing
export type EntityConfig<
    T extends EntityWithId = EntityWithId,
    FormData = Record<string, unknown>,
    FilterType extends FilterState = FilterState,
> = CustomEntityConfig<T, FormData, FilterType>;

import { SimpleEntityForm } from '@/components/common/EntityForm';
import { createSimpleEntityFilterFields } from '@/components/common/filters';
import { SimpleEntityViewModal } from '@/components/common/SimpleEntityViewModal';
import { employeeColumns } from '@/components/employees/EmployeeColumns';
import { createEmployeeFilterFields } from '@/components/employees/EmployeeFilters';
import { EmployeeForm } from '@/components/employees/EmployeeForm';
import { EmployeeViewModal } from '@/components/employees/EmployeeViewModal';
import { customerColumns } from '@/components/customers/CustomerColumns';
import { createCustomerFilterFields } from '@/components/customers/CustomerFilters';
import { CustomerForm } from '@/components/customers/CustomerForm';
import { CustomerViewModal } from '@/components/customers/CustomerViewModal';
import { supplierColumns } from '@/components/suppliers/SupplierColumns';
import { createSupplierFilterFields } from '@/components/suppliers/SupplierFilters';
import { SupplierForm } from '@/components/suppliers/SupplierForm';
import { createSimpleEntityColumns } from '@/utils/columns';
import { SupplierViewModal } from '@/components/suppliers/SupplierViewModal';
import { warehouseColumns } from '@/components/warehouses/WarehouseColumns';
import { createWarehouseFilterFields } from '@/components/warehouses/WarehouseFilters';
import { WarehouseForm } from '@/components/warehouses/WarehouseForm';
import { WarehouseViewModal } from '@/components/warehouses/WarehouseViewModal';
import { productColumns } from '@/components/products/ProductColumns';
import { createProductFilterFields } from '@/components/products/ProductFilters';
import { ProductForm } from '@/components/products/ProductForm';
import { ProductViewModal } from '@/components/products/ProductViewModal';
import { productCategoryColumns, type ProductCategory } from '@/components/product-categories/ProductCategoryColumns';
import { ProductCategoryForm } from '@/components/product-categories/ProductCategoryForm';
import { ProductCategoryViewModal } from '@/components/product-categories/ProductCategoryViewModal';
import { unitColumns, type Unit } from '@/components/units/UnitColumns';
import { UnitForm } from '@/components/units/UnitForm';
import { UnitViewModal } from '@/components/units/UnitViewModal';
import { assetCategoryColumns } from '@/components/asset-categories/AssetCategoryColumns';
import { AssetCategoryForm } from '@/components/asset-categories/AssetCategoryForm';
import { AssetCategoryViewModal } from '@/components/asset-categories/AssetCategoryViewModal';
import { type AssetCategory } from '@/types/asset-category';
import { assetColumns } from '@/components/assets/AssetColumns';
import { createAssetFilterFields } from '@/components/assets/AssetFilters';
import { AssetForm } from '@/components/assets/AssetForm';
import { AssetViewModal } from '@/components/assets/AssetViewModal';
import { type Asset } from '@/types/asset';
import { assetMovementColumns } from '@/components/asset-movements/AssetMovementColumns';
import { createAssetMovementFilterFields } from '@/components/asset-movements/AssetMovementFilters';
import { AssetMovementForm } from '@/components/asset-movements/AssetMovementForm';
import { AssetMovementViewModal } from '@/components/asset-movements/AssetMovementViewModal';
import { assetMaintenanceColumns } from '@/components/asset-maintenances/AssetMaintenanceColumns';
import { createAssetMaintenanceFilterFields } from '@/components/asset-maintenances/AssetMaintenanceFilters';
import { AssetMaintenanceForm } from '@/components/asset-maintenances/AssetMaintenanceForm';
import { AssetMaintenanceViewModal } from '@/components/asset-maintenances/AssetMaintenanceViewModal';
import { type AssetMaintenance } from '@/types/asset-maintenance';
import { stockTransferColumns } from '@/components/stock-transfers/StockTransferColumns';
import { createStockTransferFilterFields } from '@/components/stock-transfers/StockTransferFilters';
import { StockTransferForm } from '@/components/stock-transfers/StockTransferForm';
import { StockTransferViewModal } from '@/components/stock-transfers/StockTransferViewModal';
import { type StockTransfer } from '@/types/stock-transfer';
import { inventoryStocktakeColumns } from '@/components/inventory-stocktakes/InventoryStocktakeColumns';
import { createInventoryStocktakeFilterFields } from '@/components/inventory-stocktakes/InventoryStocktakeFilters';
import { InventoryStocktakeForm } from '@/components/inventory-stocktakes/InventoryStocktakeForm';
import { InventoryStocktakeViewModal } from '@/components/inventory-stocktakes/InventoryStocktakeViewModal';
import { type InventoryStocktake } from '@/types/inventory-stocktake';
import { stockAdjustmentColumns } from '@/components/stock-adjustments/StockAdjustmentColumns';
import { createStockAdjustmentFilterFields } from '@/components/stock-adjustments/StockAdjustmentFilters';
import { StockAdjustmentForm } from '@/components/stock-adjustments/StockAdjustmentForm';
import { StockAdjustmentViewModal } from '@/components/stock-adjustments/StockAdjustmentViewModal';
import { type StockAdjustment } from '@/types/stock-adjustment';
import { purchaseRequestColumns } from '@/components/purchase-requests/PurchaseRequestColumns';
import { createPurchaseRequestFilterFields } from '@/components/purchase-requests/PurchaseRequestFilters';
import { PurchaseRequestForm } from '@/components/purchase-requests/PurchaseRequestForm';
import { PurchaseRequestViewModal } from '@/components/purchase-requests/PurchaseRequestViewModal';
import { type PurchaseRequest } from '@/types/purchase-request';
import { purchaseOrderColumns } from '@/components/purchase-orders/PurchaseOrderColumns';
import { createPurchaseOrderFilterFields } from '@/components/purchase-orders/PurchaseOrderFilters';
import { PurchaseOrderForm } from '@/components/purchase-orders/PurchaseOrderForm';
import { PurchaseOrderViewModal } from '@/components/purchase-orders/PurchaseOrderViewModal';
import { type PurchaseOrder } from '@/types/purchase-order';

// Helper function to create generic delete messages
const createGenericDeleteMessage =
    (entityName: string) => (item: { name?: string }) =>
        `This action cannot be undone. This will permanently delete ${item.name || `this ${entityName.toLowerCase()}`}'s ${entityName.toLowerCase()} record.`;

// Configuration builder options
export interface SimpleEntityConfigOptions {
    entityName: string;
    entityNamePlural: string;
    apiBase: string;
    filterPlaceholder: string;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    viewModalComponent?: React.ComponentType<any>;
}

export interface ComplexEntityConfigOptions<T = Record<string, unknown>> {
    entityName: string;
    entityNamePlural: string;
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
    getDeleteMessage: (item: Record<string, unknown>) => string;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    viewModalComponent?: React.ComponentType<any>;
}

// Factory to create a bound SimpleEntityViewModal for a specific entity
function createSimpleEntityViewModal(entityName: string) {
    return function BoundSimpleEntityViewModal(props: {
        open: boolean;
        onClose: () => void;
        item: {
            id: number;
            name: string;
            created_at: string;
            updated_at: string;
        } | null;
    }) {
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
    const { entityName, entityNamePlural, apiBase, filterPlaceholder } =
        options;

    return {
        entityName,
        entityNamePlural,
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
    };
}

// Predefined configurations for simple entities
export const departmentConfig = createSimpleEntityConfig({
    entityName: 'Department',
    entityNamePlural: 'Departments',
    apiBase: 'departments',
    filterPlaceholder: 'Search departments...',
});

export const positionConfig = createSimpleEntityConfig({
    entityName: 'Position',
    entityNamePlural: 'Positions',
    apiBase: 'positions',
    filterPlaceholder: 'Search positions...',
});

export const branchConfig = createSimpleEntityConfig({
    entityName: 'Branch',
    entityNamePlural: 'Branches',
    apiBase: 'branches',
    filterPlaceholder: 'Search branches...',
});

export const warehouseConfig = createComplexEntityConfig<Warehouse>({
    entityName: 'Warehouse',
    entityNamePlural: 'Warehouses',
    apiEndpoint: '/api/warehouses',
    exportEndpoint: '/api/warehouses/export',
    queryKey: ['warehouses'],
    breadcrumbs: [{ title: 'Warehouses', href: '/warehouses' }],
    initialFilters: { search: '', branch_id: '' },
    columns: warehouseColumns,
    filterFields: createWarehouseFilterFields(),
    formComponent: WarehouseForm,
    formType: 'complex',
    entityNameForSearch: 'warehouse',
    viewModalComponent: WarehouseViewModal,
    getDeleteMessage: (item: { name?: string; code?: string }) =>
        `This action cannot be undone. This will permanently delete warehouse ${item.code} (${item.name}).`,
});

// Configuration for complex entities (employees) - using factory for consistency
export const employeeConfig = createComplexEntityConfig({
    entityName: 'Employee',
    entityNamePlural: 'Employees',
    apiEndpoint: '/api/employees',
    exportEndpoint: '/api/employees/export',
    queryKey: ['employees'],
    breadcrumbs: [{ title: 'Employees', href: '/employees' }],
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
    viewModalComponent: EmployeeViewModal,
    getDeleteMessage: (employee: { name?: string }) =>
        `This action cannot be undone. This will permanently delete ${employee.name}'s employee record.`,
});

// Configuration for complex entities (customers) - using factory for consistency
export const customerConfig = createComplexEntityConfig({
    entityName: 'Customer',
    entityNamePlural: 'Customers',
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
    viewModalComponent: CustomerViewModal,
    getDeleteMessage: (customer: { name?: string }) =>
        `This action cannot be undone. This will permanently delete ${customer.name}'s customer record.`,
});

// Configuration for complexes entities (suppliers) - using factory for consistency
export const supplierConfig = createComplexEntityConfig({
    entityName: 'Supplier',
    entityNamePlural: 'Suppliers',
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
    viewModalComponent: SupplierViewModal,
    getDeleteMessage: (supplier: { name?: string }) =>
        `This action cannot be undone. This will permanently delete ${supplier.name}'s supplier record.`,
});

export const stockTransferConfig = createComplexEntityConfig<StockTransfer>({
    entityName: 'Stock Transfer',
    entityNamePlural: 'Stock Transfers',
    apiEndpoint: '/api/stock-transfers',
    exportEndpoint: '/api/stock-transfers/export',
    queryKey: ['stock-transfers'],
    breadcrumbs: [{ title: 'Stock Transfers', href: '/stock-transfers' }],
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
    entityNameForSearch: 'stock transfer',
    viewModalComponent: StockTransferViewModal,
    getDeleteMessage: (transfer: { transfer_number?: string | null }) =>
        `This action cannot be undone. This will cancel stock transfer ${transfer.transfer_number || ''}.`,
});

export const inventoryStocktakeConfig = createComplexEntityConfig<InventoryStocktake>({
    entityName: 'Inventory Stocktake',
    entityNamePlural: 'Inventory Stocktakes',
    apiEndpoint: '/api/inventory-stocktakes',
    exportEndpoint: '/api/inventory-stocktakes/export',
    queryKey: ['inventory-stocktakes'],
    breadcrumbs: [{ title: 'Inventory Stocktakes', href: '/inventory-stocktakes' }],
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
});

export const stockAdjustmentConfig = createComplexEntityConfig<StockAdjustment>({
    entityName: 'Stock Adjustment',
    entityNamePlural: 'Stock Adjustments',
    apiEndpoint: '/api/stock-adjustments',
    exportEndpoint: '/api/stock-adjustments/export',
    queryKey: ['stock-adjustments'],
    breadcrumbs: [{ title: 'Stock Adjustments', href: '/stock-adjustments' }],
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
    viewModalComponent: StockAdjustmentViewModal,
    getDeleteMessage: (adjustment: { adjustment_number?: string | null }) =>
        `This action cannot be undone. This will cancel stock adjustment ${adjustment.adjustment_number || ''}.`,
});

export const purchaseRequestConfig = createComplexEntityConfig<PurchaseRequest>({
    entityName: 'Purchase Request',
    entityNamePlural: 'Purchase Requests',
    apiEndpoint: '/api/purchase-requests',
    exportEndpoint: '/api/purchase-requests/export',
    queryKey: ['purchase-requests'],
    breadcrumbs: [{ title: 'Purchase Requests', href: '/purchase-requests' }],
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
    filterFields: createPurchaseRequestFilterFields(),
    formComponent: PurchaseRequestForm,
    formType: 'complex',
    entityNameForSearch: 'purchase request',
    viewModalComponent: PurchaseRequestViewModal,
    getDeleteMessage: (purchaseRequest: { pr_number?: string | null }) =>
        `This action cannot be undone. This will cancel purchase request ${purchaseRequest.pr_number || ''}.`,
});

export const purchaseOrderConfig = createComplexEntityConfig<PurchaseOrder>({
    entityName: 'Purchase Order',
    entityNamePlural: 'Purchase Orders',
    apiEndpoint: '/api/purchase-orders',
    exportEndpoint: '/api/purchase-orders/export',
    queryKey: ['purchase-orders'],
    breadcrumbs: [{ title: 'Purchase Orders', href: '/purchase-orders' }],
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
    filterFields: createPurchaseOrderFilterFields(),
    formComponent: PurchaseOrderForm,
    formType: 'complex',
    entityNameForSearch: 'purchase order',
    viewModalComponent: PurchaseOrderViewModal,
    getDeleteMessage: (purchaseOrder: { po_number?: string | null }) =>
        `This action cannot be undone. This will delete purchase order ${purchaseOrder.po_number || ''}.`,
});

export const supplierCategoryConfig = createSimpleEntityConfig({
    entityName: 'Supplier Category',
    entityNamePlural: 'Supplier Categories',
    apiBase: 'supplier-categories',
    filterPlaceholder: 'Search supplier categories...',
});

export const customerCategoryConfig = createSimpleEntityConfig({
    entityName: 'Customer Category',
    entityNamePlural: 'Customer Categories',
    apiBase: 'customer-categories',
    filterPlaceholder: 'Search customer categories...',
});

export const assetCategoryConfig = createComplexEntityConfig<AssetCategory>({
    entityName: 'Asset Category',
    entityNamePlural: 'Asset Categories',
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
    viewModalComponent: AssetCategoryViewModal,
    getDeleteMessage: createGenericDeleteMessage('Asset Category'),
});

export const productCategoryConfig = createComplexEntityConfig<ProductCategory>({
    entityName: 'Product Category',
    entityNamePlural: 'Product Categories',
    apiEndpoint: '/api/product-categories',
    exportEndpoint: '/api/product-categories/export',
    queryKey: ['product-categories'],
    breadcrumbs: [{ title: 'Product Categories', href: '/product-categories' }],
    initialFilters: { search: '' },
    columns: productCategoryColumns,
    filterFields: createSimpleEntityFilterFields('Search product categories...'),
    formComponent: ProductCategoryForm,
    formType: 'complex',
    entityNameForSearch: 'product category',
    viewModalComponent: ProductCategoryViewModal,
    getDeleteMessage: createGenericDeleteMessage('Product Category'),
});

export const unitConfig = createComplexEntityConfig<Unit>({
    entityName: 'Unit',
    entityNamePlural: 'Units',
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
    viewModalComponent: UnitViewModal,
    getDeleteMessage: createGenericDeleteMessage('Unit'),
});

export const productConfig = createComplexEntityConfig({
    entityName: 'Product',
    entityNamePlural: 'Products',
    apiEndpoint: '/api/products',
    exportEndpoint: '/api/products/export',
    queryKey: ['products'],
    breadcrumbs: [{ title: 'Products', href: '/products' }],
    initialFilters: {
        search: '',
        category_id: '',
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
    viewModalComponent: ProductViewModal,
    getDeleteMessage: (product: { name?: string }) =>
        `This action cannot be undone. This will permanently delete ${product.name}'s product record.`,
});

import { fiscalYearColumns } from '@/components/fiscal-years/FiscalYearColumns';
import { createFiscalYearFilterFields } from '@/components/fiscal-years/FiscalYearFilters';
import { FiscalYearForm } from '@/components/fiscal-years/FiscalYearForm';
import { FiscalYearViewModal } from '@/components/fiscal-years/FiscalYearViewModal';
import { type FiscalYear } from '@/types/entity';

export const fiscalYearConfig = createComplexEntityConfig<FiscalYear>({
    entityName: 'Fiscal Year',
    entityNamePlural: 'Fiscal Years',
    apiEndpoint: '/api/fiscal-years',
    exportEndpoint: '/api/fiscal-years/export',
    queryKey: ['fiscal-years'],
    breadcrumbs: [{ title: 'Fiscal Years', href: '/fiscal-years' }],
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
    apiEndpoint: '/api/coa-versions',
    exportEndpoint: '/api/coa-versions/export',
    queryKey: ['coa-versions'],
    breadcrumbs: [{ title: 'COA Versions', href: '/coa-versions' }],
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
    apiEndpoint: '/api/account-mappings',
    exportEndpoint: '/api/account-mappings/export',
    queryKey: ['account-mappings'],
    breadcrumbs: [{ title: 'Account Mappings', href: '/account-mappings' }],
    initialFilters: {
        search: '',
        type: '',
        source_coa_version_id: '',
        target_coa_version_id: '',
    },
    columns: accountMappingColumns,
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
import { JournalEntryFormData } from '@/utils/schemas';

export const journalEntryConfig = createComplexEntityConfig<JournalEntry>({
    entityName: 'Journal Entry',
    entityNamePlural: 'Journal Entries',
    apiEndpoint: '/api/journal-entries',
    exportEndpoint: '/api/journal-entries/export',
    queryKey: ['journal-entries'],
    breadcrumbs: [{ title: 'Journal Entries', href: '/journal-entries' }],
    initialFilters: { search: '', status: '', start_date: '', end_date: '' },
    columns: journalEntryColumns,
    filterFields: createJournalEntryFilterFields(),
    formComponent: JournalEntryForm,
    formType: 'complex',
    entityNameForSearch: 'journal entry',
    viewModalComponent: JournalEntryViewModal,
    getDeleteMessage: (item: any) =>
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
});

import { assetLocationColumns } from '@/components/asset-locations/AssetLocationColumns';
import { createAssetLocationFilterFields } from '@/components/asset-locations/AssetLocationFilters';
import { AssetLocationForm } from '@/components/asset-locations/AssetLocationForm';
import { AssetLocationViewModal } from '@/components/asset-locations/AssetLocationViewModal';
import { type AssetLocation } from '@/types/asset-location';

export const assetLocationConfig = createComplexEntityConfig<AssetLocation>({
    entityName: 'Asset Location',
    entityNamePlural: 'Asset Locations',
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
});

export const assetConfig = createComplexEntityConfig<Asset>({
    entityName: 'Asset',
    entityNamePlural: 'Assets',
    apiEndpoint: '/api/assets',
    exportEndpoint: '/api/assets/export',
    queryKey: ['assets'],
    identifierKey: 'ulid',
    breadcrumbs: [{ title: 'Assets', href: '/assets' }],
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
});

export const assetMovementConfig = createComplexEntityConfig({
    entityName: 'Asset Movement',
    entityNamePlural: 'Asset Movements',
    apiEndpoint: '/api/asset-movements',
    exportEndpoint: '/api/asset-movements/export',
    queryKey: ['asset-movements'],
    breadcrumbs: [{ title: 'Asset Movements', href: '/asset-movements' }],
    initialFilters: {
        search: '',
        asset_id: '',
        movement_type: '',
    },
    columns: assetMovementColumns as any,
    filterFields: createAssetMovementFilterFields(),
    formComponent: AssetMovementForm,
    formType: 'complex',
    entityNameForSearch: 'movement',
    viewModalComponent: AssetMovementViewModal as any,
    getDeleteMessage: () => 'This action cannot be undone. This will permanently delete this movement record.',
});

export const assetMaintenanceConfig = createComplexEntityConfig<AssetMaintenance>({
    entityName: 'Asset Maintenance',
    entityNamePlural: 'Asset Maintenances',
    apiEndpoint: '/api/asset-maintenances',
    exportEndpoint: '/api/asset-maintenances/export',
    queryKey: ['asset-maintenances'],
    breadcrumbs: [{ title: 'Asset Maintenances', href: '/asset-maintenances' }],
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
    getDeleteMessage: () => 'This action cannot be undone. This will permanently delete this maintenance record.',
});

import { assetStocktakeColumns } from '@/components/asset-stocktakes/AssetStocktakeColumns';
import { createAssetStocktakeFilterFields } from '@/components/asset-stocktakes/AssetStocktakeFilters';
import { AssetStocktakeForm } from '@/components/asset-stocktakes/AssetStocktakeForm';
import { AssetStocktakeViewModal } from '@/components/asset-stocktakes/AssetStocktakeViewModal';
import { type AssetStocktake } from '@/types/asset-stocktake';

export const assetStocktakeConfig = createComplexEntityConfig<AssetStocktake>({
    entityName: 'Asset Stocktake',
    entityNamePlural: 'Asset Stocktakes',
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
});

import { pipelineColumns } from '@/components/pipelines/PipelineColumns';
import { createPipelineFilterFields } from '@/components/pipelines/PipelineFilters';
import { PipelineForm } from '@/components/pipelines/PipelineForm';
import { PipelineViewModal } from '@/components/pipelines/PipelineViewModal';
import { type Pipeline } from '@/types/entity';

export const pipelineConfig = createComplexEntityConfig<Pipeline>({
    entityName: 'Pipeline',
    entityNamePlural: 'Pipelines',
    apiEndpoint: '/api/pipelines',
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

export const approvalFlowConfig = createComplexEntityConfig<any>({
    entityName: 'Approval Flow',
    entityNamePlural: 'Approval Flows',
    apiEndpoint: '/api/approval-flows',
    exportEndpoint: '/api/approval-flows/export',
    queryKey: ['approval-flows'],
    breadcrumbs: [{ title: 'Approval Flows', href: '/approval-flows' }],
    initialFilters: { search: '', approvable_type: '', is_active: '' },
    columns: approvalFlowColumns as any,
    filterFields: createApprovalFlowFilterFields(),
    formComponent: ApprovalFlowForm,
    formType: 'complex',
    entityNameForSearch: 'approval flow',
    viewModalComponent: ApprovalFlowViewModal,
    getDeleteMessage: (item: { name?: string }) =>
        `This action cannot be undone. This will permanently delete the approval flow "${item.name}".`,
});

import { approvalDelegationColumns } from '@/components/approval-delegations/ApprovalDelegationColumns';
import { createApprovalDelegationFilterFields } from '@/components/approval-delegations/ApprovalDelegationFilters';
import { ApprovalDelegationForm } from '@/components/approval-delegations/ApprovalDelegationForm';
import { ApprovalDelegationViewModal } from '@/components/approval-delegations/ApprovalDelegationViewModal';

export const approvalDelegationConfig = createComplexEntityConfig<any>({
    entityName: 'Approval Delegation',
    entityNamePlural: 'Approval Delegations',
    apiEndpoint: '/api/approval-delegations',
    exportEndpoint: '/api/approval-delegations/export',
    queryKey: ['approval-delegations'],
    breadcrumbs: [{ title: 'Approval Delegations', href: '/approval-delegations' }],
    initialFilters: { search: '', delegator_user_id: '', delegate_user_id: '', is_active: '', start_date_from: '', start_date_to: '' },
    columns: approvalDelegationColumns as any,
    filterFields: createApprovalDelegationFilterFields(),
    formComponent: ApprovalDelegationForm,
    formType: 'complex',
    entityNameForSearch: 'approval delegation',
    viewModalComponent: ApprovalDelegationViewModal,
    getDeleteMessage: () =>
        `This action cannot be undone. This will permanently delete the selected approval delegation.`,
});
