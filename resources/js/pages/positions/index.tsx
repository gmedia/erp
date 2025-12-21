'use client';

import { createSimpleEntityCrudPage } from '@/components/common/SimpleEntityCrudPage';
import { Position, PositionFormData, SimpleEntityFilters } from '@/types/entity';
import { positionConfig } from '@/utils/entityConfigs';

export default createSimpleEntityCrudPage<Position, PositionFormData, SimpleEntityFilters>(positionConfig);
