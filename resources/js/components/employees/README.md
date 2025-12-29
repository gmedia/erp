# Employee Components

This directory contains components specific to the Employee module.

## Overview

The Employee module handles complex entity CRUD operations with the following structure:

- **Entity**: Employee (name, email, phone, department, position, salary, hire_date, created_at, updated_at)
- **Form**: Multiple fields with validation (name, email, phone, department, position, salary, hire_date)
- **Table**: Complex columns (select, name, email, phone, department, position, salary, hire_date, actions)
- **Filters**: Advanced filters (search, department, position)

## Components

- `EmployeeColumns.tsx` - Column definitions for the employee data table
- `EmployeeFilters.tsx` - Filter field definitions for employee search and filtering
- `EmployeeForm.tsx` - Form component for creating and editing employees

## Usage

The employee page is created using:

```tsx
import { createEntityCrudPage } from '@/components/common/SimpleEntityCrudPage';
import { employeeConfig } from '@/utils/entityConfigs';

export default createEntityCrudPage(employeeConfig);
```

The configuration is defined in `@/utils/entityConfigs.ts` and includes:

- API endpoints
- Breadcrumbs
- Export endpoints
- Initial filters
- Delete confirmation messages

## Features

- **Complex Form Validation**: Uses Zod schema for comprehensive validation
- **Configuration-Driven Forms**: Form fields defined via configuration array for maintainability
- **Dropdown Selections**: Department and position selection from predefined constants
- **Date Picker**: Hire date selection with validation
- **Email/Phone Links**: Clickable email and phone columns in the table
- **Currency Formatting**: Salary display with proper formatting
- **Advanced Filtering**: Search, department, and position filters

## Form Fields

| Field      | Type   | Validation                  | Required |
| ---------- | ------ | --------------------------- | -------- |
| Name       | Text   | 2+ characters               | Yes      |
| Email      | Email  | Valid email format          | Yes      |
| Phone      | Text   | 10+ digits                  | Yes      |
| Department | Select | From constants              | Yes      |
| Position   | Select | From constants              | Yes      |
| Salary     | Number | Valid decimal               | Yes      |
| Hire Date  | Date   | Not future, not before 1900 | Yes      |

## Recent Improvements

- **Configuration-Driven Rendering**: Form fields are now defined via a configuration array
- **Better Type Safety**: Improved TypeScript usage with proper form typing
- **Generic Field Renderer**: Standardized form field rendering logic
- **Helper Functions**: Extracted default value generation for better maintainability

## Related Files

- `@/types/employee.d.ts` - TypeScript interfaces
- `@/utils/schemas.ts` - Validation schemas
- `@/constants.ts` - Department and position options
- `@/utils/columns.tsx` - Column builder utilities
