import { generateModuleTests } from '../shared-test-factories';
import {
    createStockTransfer,
    editStockTransfer,
    searchStockTransfer,
} from './helpers';

generateModuleTests({
    entityName: 'Stock Transfer',
    entityNamePlural: 'Stock Transfers',
    route: '/stock-transfers',
    apiPath: '/api/stock-transfers',

    createEntity: (page) => createStockTransfer(page),
    searchEntity: (page, identifier) => searchStockTransfer(page, identifier),
    editEntity: (page, identifier, updates) => editStockTransfer(page, identifier, updates),
    editUpdates: { transfer_number: `ST-UPDATED-${Date.now()}` },

    sortableColumns: [
        'Transfer Number',
        'From Warehouse',
        'To Warehouse',
        'Transfer Date',
        'Expected Arrival',
        'Status',
    ],

    viewType: 'dialog',

    exportApiPath: '/api/stock-transfers/export',
    expectedExportColumns: [
        'ID',
        'Transfer Number',
        'From Warehouse',
        'To Warehouse',
        'Transfer Date',
        'Expected Arrival Date',
        'Status',
        'Created At',
    ],
});
