# Employee Components

This directory contains components specific to the Employee module.

## Overview

The Employee module provides complex entity CRUD operations for employee management with comprehensive form validation and advanced filtering. The components have been refactored for better maintainability and performance.

## Components

- `EmployeeColumns.tsx` - Column definitions for the employee data table
- `EmployeeFilters.tsx` - Filter field definitions for employee search and filtering
- `EmployeeForm.tsx` - Form component for creating and editing employees with organized sections

## Usage

The employee page is created using the configuration-driven factory:

```tsx
import { createEntityCrudPage } from '@/components/common/EntityCrudPage';
import { employeeConfig } from '@/utils/entityConfigs';

export default createEntityCrudPage(employeeConfig);
```

## Features

- **Comprehensive Form Validation**: Zod schema validation for all fields
- **Rich Data Types**: Support for text, email, phone, currency, date, and select fields
- **Advanced Filtering**: Search, department, and position filters
- **Interactive Table**: Sortable columns with email/phone links and currency formatting
- **Date Handling**: Hire date picker with validation constraints
- **Performance Optimized**: Memoized components and optimized re-renders

## Form Fields

| Field      | Type   | Validation                  | Required |
| ---------- | ------ | --------------------------- | -------- |
| Name       | Text   | 2+ characters               | Yes      |
| Email      | Email  | Valid email format          | Yes      |
| Phone      | Text   | 10+ digits                  | Yes      |
| Department | Select | From predefined options     | Yes      |
| Position   | Select | From predefined options     | Yes      |
| Salary     | Number | Valid decimal               | Yes      |
| Hire Date  | Date   | Not future, not before 1900 | Yes      |

## Recent Improvements

### EmployeeForm Refactoring
- **Modular Form Sections**: Form is now organized into logical sections (Basic Info, Work Info, Hire Date)
- **Performance Optimization**: Added memoization to prevent unnecessary re-renders
- **Better Maintainability**: Separated form logic from presentation components
- **Type Safety**: Improved TypeScript usage throughout the component

## Related Files

- `@/types/employee.ts` - TypeScript interfaces
- `@/utils/schemas.ts` - Validation schemas
- `@/constants.ts` - Department and position options
- `@/utils/columns.tsx` - Column builder utilities
