'use client';

import { createEntityCrudPage } from '@/components/common/SimpleEntityCrudPage';
import { employeeConfig } from '@/utils/entityConfigs';

export default createEntityCrudPage(employeeConfig);
