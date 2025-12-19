'use client';

import { createSimpleEntityFilterFields } from '@/components/common/filters';

// Department-specific filter fields
export function createDepartmentFilterFields() {
    return createSimpleEntityFilterFields("Search departments...");
}
