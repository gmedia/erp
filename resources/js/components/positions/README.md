# Position Components

This directory contains components specific to the Position module.

## Overview

The Position module uses simple entity CRUD operations with the following structure:
- **Entity**: Position (name, created_at, updated_at)
- **Form**: Simple name field
- **Table**: Basic columns (select, name, created_at, updated_at, actions)
- **Filters**: Simple search filter

## Components

Since positions use the shared `SimpleEntityCrudPage` factory function, this directory is primarily for future extensibility. The actual components are provided by the common components:

- `SimpleEntityForm` from `@/components/common/EntityForm`
- `GenericDataTable` from `@/components/common/GenericDataTable`
- Column definitions from `@/utils/columns`

## Usage

The position page is created using:

```tsx
export default createSimpleEntityCrudPage<Position, PositionFormData, SimpleEntityFilters>({
    entityName: 'Position',
    entityNamePlural: 'Positions',
    apiEndpoint: '/api/positions',
    queryKey: ['positions'],
    breadcrumbs,
    exportEndpoint: '/api/positions/export',
    filterPlaceholder: 'Search positions...',
    getDeleteMessage: (position) =>
        `This action cannot be undone. This will permanently delete ${position.name}'s position record.`,
});
```

## Future Extensions

If positions need custom components in the future, they should be added here following the same pattern as the employees module.
