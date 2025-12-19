'use client';

import { createSimpleEntityFilterFields } from '@/components/common/filters';

// Position-specific filter fields
export function createPositionFilterFields() {
    return createSimpleEntityFilterFields("Search positions...");
}
