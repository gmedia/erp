# Department Components

This directory contains components specific to the Department module.

## Overview

The Department module provides simple entity CRUD operations for organizational departments.

## Usage

The department page is created using the configuration-driven factory:

```tsx
import { createEntityCrudPage } from '@/components/common/EntityCrudPage';
import { departmentConfig } from '@/utils/entityConfigs';

export default createEntityCrudPage(departmentConfig);
```

## Configuration

Department configuration includes:

- API endpoints (`/api/departments`)
- Simple form with name field only
- Basic table columns (select, name, created_at, updated_at, actions)
- Search filtering
- Standard delete confirmation

## Future Extensions

If departments require custom components beyond the standard CRUD operations, add them here following the employee module pattern.
