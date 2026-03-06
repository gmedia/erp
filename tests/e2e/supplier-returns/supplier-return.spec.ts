import { test } from '@playwright/test';

import { generateModuleTests, ModuleTestConfig } from '../shared-test-factories';
import {
    createSupplierReturn,
    editSupplierReturn,
    searchSupplierReturn,
} from './helpers';

const config: ModuleTestConfig = {
    entityName: 'Supplier Return',
    entityNamePlural: 'Supplier Returns',
    route: '/supplier-returns',
    apiPath: '/api/supplier-returns',
    exportApiPath: '/api/supplier-returns/export',
    createEntity: createSupplierReturn,
    searchEntity: searchSupplierReturn,
    editEntity: editSupplierReturn,
    editUpdates: { return_number: 'SR-E2E-UPDATED-001' },
    expectedExportColumns: [
        'ID',
        'Return Number',
        'PO Number',
        'GR Number',
        'Supplier',
        'Warehouse',
        'Return Date',
        'Reason',
        'Status',
        'Notes',
        'Created At',
    ],
    sortableColumns: [
        'Return Number',
        'PO Number',
        'GR Number',
        'Supplier',
        'Warehouse',
        'Return Date',
        'Reason',
        'Status',
    ],
    viewType: 'dialog',
    viewDialogTitle: 'Supplier Return Details',
};

test.describe('Supplier Returns Module', () => {
    generateModuleTests(config);
});
