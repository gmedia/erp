'use client';

import { createEntityCrudPage } from '@/components/common/EntityCrudPage';
import ImportDialog from '@/components/common/ImportDialog';
import { assetConfig } from '@/utils/entityConfigs';

const config = {
    ...assetConfig,
    defaultColumnVisibility: {
        branch: false,
        department: false,
        supplier: false,
        location: false,
        purchase_date: false,
    },
    toolbarActions: (
        <ImportDialog
            permission="asset.create"
            title="Import Assets"
            importRoute="/api/assets/import"
            templateHeaders={[
                'asset_code',
                'name',
                'asset_category',
                'asset_model',
                'branch',
                'location',
                'department',
                'employee',
                'supplier',
                'serial_number',
                'barcode',
                'purchase_date',
                'purchase_cost',
                'currency',
                'warranty_end_date',
                'status',
                'condition',
                'notes',
            ]}
        />
    ),
};

export default createEntityCrudPage(config);
