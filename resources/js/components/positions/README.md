# Position Components

This directory contains components specific to the Position module.

## Overview

The Position module provides simple entity CRUD operations for job roles and titles.

## Usage

The position page is created using the configuration-driven factory:

```tsx
import { createEntityCrudPage } from '@/components/common/EntityCrudPage';
import { positionConfig } from '@/utils/entityConfigs';

export default createEntityCrudPage(positionConfig);
```

## Configuration

Position configuration includes:

- API endpoints (`/api/positions`)
- Simple form with name field only
- Basic table columns (select, name, created_at, updated_at, actions)
- Search filtering
- Standard delete confirmation

## Future Extensions

If positions require custom components beyond the standard CRUD operations, add them here following the employee module pattern.
