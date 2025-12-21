'use client';

import { createEntityCrudPage } from '@/components/common/SimpleEntityCrudPage';
import { Position, PositionFormData, SimpleEntityFilters } from '@/types/entity';
import { positionConfig } from '@/utils/entityConfigs';

export default createEntityCrudPage<Position, PositionFormData, SimpleEntityFilters>(positionConfig);
