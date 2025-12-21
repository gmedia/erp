'use client';

import { createEntityCrudPage } from '@/components/common/SimpleEntityCrudPage';
import { Department, DepartmentFormData, SimpleEntityFilters } from '@/types/entity';
import { departmentConfig } from '@/utils/entityConfigs';

export default createEntityCrudPage<Department, DepartmentFormData, SimpleEntityFilters>(departmentConfig);
