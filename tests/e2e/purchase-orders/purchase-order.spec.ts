import { test } from '@playwright/test';

import { generateModuleTests, ModuleTestConfig } from '../shared-test-factories';
import {
    createPurchaseOrder,
    editPurchaseOrder,
    searchPurchaseOrder,
} from './helpers';

const config: ModuleTestConfig = {
    entityName: 'Purchase Order',
    entityNamePlural: 'Purchase Orders',
    route: '/purchase-orders',
    apiPath: '/api/purchase-orders',
    exportApiPath: '/api/purchase-orders/export',
    createEntity: createPurchaseOrder,
    searchEntity: searchPurchaseOrder,
    editEntity: editPurchaseOrder,
    editUpdates: { po_number: 'PO-E2E-UPDATED-001' },
    expectedExportColumns: [
        'ID',
        'PO Number',
        'Supplier',
        'Warehouse',
        'Order Date',
        'Expected Delivery Date',
        'Payment Terms',
        'Currency',
        'Status',
        'Subtotal',
        'Tax Amount',
        'Discount Amount',
        'Grand Total',
        'Notes',
        'Created At',
    ],
    sortableColumns: [
        'PO Number',
        'Supplier',
        'Warehouse',
        'Order Date',
        'Expected Delivery',
        'Status',
        'Grand Total',
    ],
    viewType: 'dialog',
    viewDialogTitle: 'Purchase Order Details',
};

test.describe('Purchase Orders Module', () => {
    generateModuleTests(config);
});
