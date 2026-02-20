'use client';

import { createEntityCrudPage } from '@/components/common/EntityCrudPage';
import ImportDialog from '@/components/common/ImportDialog';
import { employeeConfig } from '@/utils/entityConfigs';

const config = {
  ...employeeConfig,
  toolbarActions: (
    <ImportDialog
      title="Import Employees"
      importRoute="/api/employees/import"
      templateHeaders={[
        'employee_id',
        'name',
        'email',
        'phone',
        'department',
        'position',
        'branch',
        'salary',
        'hire_date',
        'employment_status',
        'termination_date',
      ]}
    />
  ),
};

export default createEntityCrudPage(config);
