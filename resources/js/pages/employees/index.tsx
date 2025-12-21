'use client';

import { createEntityCrudPage } from '@/components/common/SimpleEntityCrudPage';
import { Employee, EmployeeFormData } from '@/types/entity';
import { employeeConfig, EmployeeFilters } from '@/utils/entityConfigs';

export default createEntityCrudPage<Employee, EmployeeFormData, EmployeeFilters>(employeeConfig);
