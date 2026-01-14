# Feature Documentation: Remote Departments and Positions

This document outlines the changes made to move Department and Position data selection from frontend constants to backend tables.

## Changes Overview

### Frontend

1.  **AsyncSelect Component**: Created `AsyncSelect` and `AsyncSelectField` to handle remote data fetching, searching, and selection.
    - Uses `axios` to fetch data from `/api/departments` and `/api/positions`.
    - Supports debounced searching.
    - Uses Shadcn UI `Popover` and `Input` for a searchable dropdown experience.
    - Accessible (roles `listbox`, `option`).
2.  **EmployeeForm**: Refactored to use `AsyncSelectField` instead of `SelectField`.
    - Removes dependency on `DEPARTMENTS` and `POSITIONS` constants.
    - Submits the ID of the selected entity (e.g., "1") instead of label code (e.g., "hr").
3.  **EmployeeFilters**: Refactored to use `createAsyncSelectFilterField`.
    - Filters by exact ID matching (backend logic).

### Backend

1.  **Seeders**: Added `DepartmentSeeder` and `PositionSeeder` to populate `departments` and `positions` tables with initial data (matching original constants).
    - Synced in `DatabaseSeeder`.
2.  **Validation**: Updated `StoreEmployeeRequest` and `UpdateEmployeeRequest`.
    - Changed validation for `department` and `position` from fixed `in:list` to `exists:departments,id` and `exists:positions,id`.

### Data Migration Note

- Existing data in `employees` table uses string codes (e.g., "hr", "engineering").
- New data will use stringified IDs (e.g., "1", "2").
- **Impact**: When editing an existing employee with "hr" department, the `AsyncSelect` will try to find Department with ID "hr", which fails (404/Empty). The form will show ID or empty label depending on behavior.
- **Action Required**: A data migration is recommended to update existing `employees` records to map "hr" -> "1", etc., if existing data needs to be preserved seamlessly.

### Tests

- **E2E (Playwright)**: Updated to support `AsyncSelect` interaction (using `role="option"`). All tests pass.
- **Unit/Feature (PHPUnit)**: Updated `EmployeeControllerTest` to use valid Department/Position IDs in requests instead of static strings. All tests pass.
