import { test } from '@playwright/test';

import { generateModuleTests, ModuleTestConfig } from '../shared-test-factories';
import {
    createPurchaseRequest,
    editPurchaseRequest,
    searchPurchaseRequest,
} from './helpers';

const config: ModuleTestConfig = {
    entityName: 'Purchase Request',
    entityNamePlural: 'Purchase Requests',
    route: '/purchase-requests',
    apiPath: '/api/purchase-requests',
    exportApiPath: '/api/purchase-requests/export',
    createEntity: createPurchaseRequest,
    searchEntity: searchPurchaseRequest,
    editEntity: editPurchaseRequest,
    editUpdates: { pr_number: 'PR-E2E-UPDATED-001' },
    expectedExportColumns: [
        'ID',
        'PR Number',
        'Branch',
        'Department',
        'Requested By',
        'Request Date',
        'Required Date',
        'Priority',
        'Status',
        'Estimated Amount',
        'Notes',
        'Created At',
    ],
    sortableColumns: [
        'PR Number',
        'Branch',
        'Department',
        'Requester',
        'Request Date',
        'Required Date',
        'Priority',
        'Status',
        'Estimated Amount',
    ],
    viewType: 'dialog',
    viewDialogTitle: 'Purchase Request Details',
};

test.describe('Purchase Requests Module', () => {
    generateModuleTests(config);
});
