import { generateModuleTests } from '../shared-test-factories';
import { createApprovalFlow, searchApprovalFlow, editApprovalFlow } from './helpers';

generateModuleTests({
    entityName: 'Approval Flow',
    entityNamePlural: 'Approval Flows',
    route: '/approval-flows',
    apiPath: '/api/approval-flows',

    createEntity: createApprovalFlow,
    searchEntity: searchApprovalFlow,
    editEntity: editApprovalFlow,
    editUpdates: {
        name: 'Updated Flow Name',
    },

    viewType: 'dialog',
    // viewDialogTitle is usually dynamic based on the item name for this modal,
    // so we omit verifying a specific hardcoded title, relying on the default matching logic

    sortableColumns: ['Code', 'Name', 'Status', 'Created At'],

    exportApiPath: '/api/approval-flows/export',
    expectedExportColumns: [
        'ID',
        'Code',
        'Name',
        'Approvable Type',
        'Is Active',
        'Created By',
        'Created At',
    ],
});
