import { type FormComponentType } from '@/components/common/EntityCrudPage';
import { type FieldDescriptor } from '@/components/common/filters';
import { type FilterState } from '@/hooks/useCrudFilters';
import { type BreadcrumbItem } from '@/types';
import { type EntityWithId } from '@/types/entity';
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
import { AssetMovementForm } from '@/components/assets/AssetMovementForm';
import { AssetMovementViewModal } from '@/components/asset-movements/AssetMovementViewModal';

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
