'use client';

import { createSimpleEntityCrudPage } from '@/components/common/SimpleEntityCrudPage';
import { Department, DepartmentFormData, SimpleEntityFilters } from '@/types/entity';
import { departmentConfig } from '@/utils/entityConfigs';

export default createSimpleEntityCrudPage<Department, DepartmentFormData, SimpleEntityFilters>(departmentConfig);
