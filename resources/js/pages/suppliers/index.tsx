'use client';

import { createEntityCrudPage } from '@/components/common/EntityCrudPage';
import ImportDialog from '@/components/common/ImportDialog';
import { supplierConfig } from '@/utils/entityConfigs';

const config = {
    ...supplierConfig,
    toolbarActions: (
        <ImportDialog
            title="Import Suppliers"
            importRoute="/api/suppliers/import"
            templateHeaders={[
                'name',
                'email',
                'phone',
                'address',
                'branch',
                'category',
                'status',
            ]}
        />
    ),
};

export default createEntityCrudPage(config);
