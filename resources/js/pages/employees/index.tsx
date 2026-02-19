'use client';

import { createEntityCrudPage } from '@/components/common/EntityCrudPage';
import ImportDialog from '@/Components/Dialogs/ImportDialog';
import { employeeConfig } from '@/utils/entityConfigs';

const config = {
  ...employeeConfig,
  toolbarActions: (
    <ImportDialog
      title="Import Employees"
      importRoute="/api/employees/import"
      templateHeaders={[
        'name',
        'email',
        'phone',
        'department',
        'position',
        'branch',
        'salary',
        'hire_date',
      ]}
    />
  ),
};

export default createEntityCrudPage(config);
