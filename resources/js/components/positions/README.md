# Simple Entity Components

This directory contains components for simple entities (Departments, Positions) that follow a standardized CRUD pattern.

## Overview

Simple entities use shared, configuration-driven components for basic CRUD operations:

- **Entity Structure**: Basic entities with `name`, `created_at`, `updated_at` fields
- **Form**: Single name input field with validation
- **Table**: Standard columns (select, name, created_at, updated_at, actions)
- **Filters**: Simple search functionality

## Shared Components

All simple entities use the common component system:

- `SimpleEntityForm` - Standardized form for name-only entities
- `DataTable` - Generic table with built-in sorting, filtering, and pagination
- Column builders from `@/utils/columns` for consistent table structure

## Usage Pattern

Simple entity pages are created using the configuration-driven factory:

```tsx
import { createEntityCrudPage } from '@/components/common/EntityCrudPage';
import { departmentConfig } from '@/utils/entityConfigs';

export default createEntityCrudPage(departmentConfig);
```

## Configuration

Entity configurations are defined in `@/utils/entityConfigs.ts` and include:

- API endpoints and export URLs
- Navigation breadcrumbs
- Filter configurations
- Delete confirmation messages
- Component mappings

## Extending Simple Entities

If a simple entity needs custom behavior:

1. Add custom components to the respective directory
2. Update the entity configuration to use custom components
3. Follow the employee module pattern for complex customizations

## Current Simple Entities

- **Departments**: Basic organizational units
- **Positions**: Job roles and titles
