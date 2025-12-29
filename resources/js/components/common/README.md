# CRUD Components

This directory contains reusable components for building CRUD (Create, Read, Update, Delete) interfaces. The components are designed to eliminate code duplication across different entity types (departments, positions, employees) by providing configurable, generic solutions.

## Components Overview

### CrudPage

A generic, reusable page component that handles complete CRUD workflows for any entity type.

### EntityCrudPage

A factory function that creates CRUD pages for any entity type using configuration-driven approach. Eliminates code duplication by using unified entity configurations that include all necessary components, columns, and filters.

### DataTable & Related Components

Reusable data table components with built-in filtering, pagination, sorting, and export functionality.

## Features

- ✅ Complete CRUD workflow handling
- ✅ Modal state management for forms and confirmations
- ✅ Integrated with reusable hooks (`useCrudQuery`, `useCrudMutations`, `useCrudFilters`)
- ✅ TypeScript generics for type safety
- ✅ Flexible configuration for different entity types
- ✅ Loading states and error handling
- ✅ Pagination and filtering support
- ✅ Customizable delete messages

## Usage

### Basic Usage

```tsx
import { CrudPage } from '@/components/common/CrudPage';
import { MyDataTable } from '@/components/my-entity/MyDataTable';
import { MyForm } from '@/components/my-entity/MyForm';
import { MyEntity, MyEntityFormData } from '@/types/my-entity';

export default function MyEntityIndex() {
    return (
        <CrudPage<MyEntity, MyEntityFormData>
            config={{
                entityName: 'MyEntity',
                entityNamePlural: 'MyEntities',
                apiEndpoint: '/api/my-entities',
                queryKey: ['my-entities'],
                breadcrumbs: [{ title: 'My Entities', href: '/my-entities' }],
                DataTableComponent: MyDataTable,
                FormComponent: MyForm,

                // Map generic props to component-specific props
                mapDataTableProps: (props) => ({
                    data: props.data,
                    onAdd: props.onAdd,
                    onEdit: props.onEdit,
                    onDelete: props.onDelete,
                    // ... other props
                }),

                mapFormProps: (props) => ({
                    open: props.open,
                    onOpenChange: props.onOpenChange,
                    item: props.item,
                    onSubmit: props.onSubmit,
                    isLoading: props.isLoading,
                }),
            }}
        />
    );
}
```

### Advanced Usage with Custom Filters

```tsx
interface CustomFilters {
    search: string;
    category: string;
    status: string;
}

export default function ProductIndex() {
    return (
        <CrudPage<Product, ProductFormData, CustomFilters>
            config={{
                entityName: 'Product',
                entityNamePlural: 'Products',
                apiEndpoint: '/api/products',
                queryKey: ['products'],
                breadcrumbs: [{ title: 'Products', href: '/products' }],

                // Custom initial filters
                initialFilters: {
                    search: '',
                    category: '',
                    status: 'active',
                },

                DataTableComponent: ProductDataTable,
                FormComponent: ProductForm,

                // Custom delete message
                getDeleteMessage: (product) =>
                    `Delete product "${product.name}"? This action cannot be undone.`,

                mapDataTableProps: (props) => ({
                    data: props.data,
                    onAddProduct: props.onAdd,
                    onEditProduct: props.onEdit,
                    onDeleteProduct: props.onDelete,
                    pagination: props.pagination,
                    onPageChange: props.onPageChange,
                    onPageSizeChange: props.onPageSizeChange,
                    onSearchChange: props.onSearchChange,
                    isLoading: props.isLoading,
                    filters: props.filters,
                    onFilterChange: props.onFilterChange,
                    onResetFilters: props.onResetFilters,
                }),

                mapFormProps: (props) => ({
                    open: props.open,
                    onOpenChange: props.onOpenChange,
                    product: props.item,
                    onSubmit: props.onSubmit,
                    isLoading: props.isLoading,
                }),
            }}
        />
    );
}
```

## Configuration Options

### Required Properties

| Property             | Type                | Description                                      |
| -------------------- | ------------------- | ------------------------------------------------ |
| `entityName`         | string              | Singular name of the entity (e.g., "Department") |
| `entityNamePlural`   | string              | Plural name of the entity (e.g., "Departments")  |
| `apiEndpoint`        | string              | API endpoint for CRUD operations                 |
| `queryKey`           | string[]            | React Query key for caching                      |
| `breadcrumbs`        | BreadcrumbItem[]    | Navigation breadcrumbs                           |
| `DataTableComponent` | React.ComponentType | Component for displaying data table              |
| `FormComponent`      | React.ComponentType | Component for create/edit forms                  |
| `mapDataTableProps`  | function            | Maps generic props to DataTable component props  |
| `mapFormProps`       | function            | Maps generic props to Form component props       |

### Optional Properties

| Property            | Type     | Description                        |
| ------------------- | -------- | ---------------------------------- |
| `initialFilters`    | object   | Initial filter state               |
| `initialPagination` | object   | Initial pagination state           |
| `getDeleteMessage`  | function | Custom delete confirmation message |
| `onCreateSuccess`   | function | Callback after successful creation |
| `onUpdateSuccess`   | function | Callback after successful update   |
| `onDeleteSuccess`   | function | Callback after successful deletion |
| `onError`           | function | Error callback                     |

## Migration Guide

### From Department Index (Before)

```tsx
// resources/js/pages/departments/index.tsx (295 lines)
// Complex state management, mutations, queries
```

### To Department Index (After)

```tsx
// resources/js/pages/departments/index.new.tsx (54 lines)
import { CrudPage } from '@/components/common/CrudPage';
// Simple configuration-based approach
```

### Migration Steps

1. **Create new index file** using CrudPage component
2. **Map component props** using `mapDataTableProps` and `mapFormProps`
3. **Test functionality** to ensure all features work correctly
4. **Replace old file** once verified

## Component Interface Requirements

### DataTableComponent Props

Your DataTable component should accept these props (mapped via `mapDataTableProps`):

- `data`: Array of entity items
- Pagination props
- Filter props
- Event handlers for add, edit, delete
- Loading states

### FormComponent Props

Your Form component should accept these props (mapped via `mapFormProps`):

- `open`: Boolean for modal state
- `onOpenChange`: Function to control modal
- `item`: Current item for editing (null for new)
- `onSubmit`: Function to handle form submission
- `isLoading`: Loading state

## TypeScript Support

The component uses TypeScript generics for full type safety:

```tsx
CrudPage<EntityType, FormDataType, FilterType>;
```

- `EntityType`: Your entity interface (e.g., Department, Employee)
- `FormDataType`: Your form data interface (e.g., DepartmentFormData)
- `FilterType`: Your filters interface (optional, defaults to Record<string, any>)

## Examples

The current implementation uses the `createEntityCrudPage` factory function which provides:

- **Departments & Positions**: Simple entity CRUD with name field only
- **Employees**: Complex entity CRUD with multiple fields (name, email, phone, department, position, salary, hire_date)

## Usage Examples

### Simple Entity (Departments/Positions)

```tsx
import { createEntityCrudPage } from '@/components/common/EntityCrudPage';
import { departmentConfig } from '@/utils/entityConfigs';

export default createEntityCrudPage(departmentConfig);
```

### Complex Entity (Employees)

```tsx
import { createEntityCrudPage } from '@/components/common/EntityCrudPage';
import { employeeConfig } from '@/utils/entityConfigs';

export default createEntityCrudPage(employeeConfig);
```

The factory function uses configuration-driven approach where all components, columns, and filters are specified in the entity configuration.

## Architecture Overview

The CRUD components follow a configuration-driven architecture that eliminates code duplication:

### Key Components

- **`CrudPage`**: Generic page component handling complete CRUD workflows
- **`EntityCrudPage`**: Factory function creating pages from entity configurations
- **`DataTable`**: Reusable table with filtering, pagination, and sorting
- **`EntityForm`**: Generic form wrapper with validation support

### Configuration-Driven Design

All CRUD pages are created using entity configurations defined in `@/utils/entityConfigs.ts`:

```tsx
// Simple entities (departments, positions)
export default createEntityCrudPage(departmentConfig);

// Complex entities (employees)
export default createEntityCrudPage(employeeConfig);
```

This approach provides:

- **Consistency**: Standardized patterns across all entities
- **Maintainability**: Changes to common behavior affect all entities
- **Extensibility**: New entities can be added with minimal code
- **Type Safety**: Full TypeScript support with proper generics
