# Department Components

This directory contains components specific to the Department module.

## Overview

The Department module uses simple entity CRUD operations with the following structure:
- **Entity**: Department (name, created_at, updated_at)
- **Form**: Simple name field
- **Table**: Basic columns (select, name, created_at, updated_at, actions)
- **Filters**: Simple search filter

## Components

Since departments use the shared `createEntityCrudPage` factory function, this directory is primarily for future extensibility. The actual components are provided by the common components:

- `SimpleEntityForm` from `@/components/common/EntityForm`
- `GenericDataTable` from `@/components/common/GenericDataTable`
- Column definitions from `@/utils/columns`

## Usage

The department page is created using:

```tsx
import { createEntityCrudPage } from '@/components/common/SimpleEntityCrudPage';
import { departmentConfig } from '@/utils/entityConfigs';

export default createEntityCrudPage(departmentConfig);
```

The configuration is defined in `@/utils/entityConfigs.ts` and includes:
- API endpoints
- Breadcrumbs
- Export endpoints
- Filter placeholders
- Delete confirmation messages

## Future Extensions

If departments need custom components in the future, they should be added here following the same pattern as the employees module.
