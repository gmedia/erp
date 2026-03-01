import { generateModuleTests } from '../shared-test-factories';
import { createApprovalDelegation, searchApprovalDelegation, editApprovalDelegation } from './helpers';

generateModuleTests({
  entityName: 'Approval Delegation',
  entityNamePlural: 'Approval Delegations',
  route: '/approval-delegations',
  apiPath: '/api/approval-delegations',
  exportApiPath: '/api/approval-delegations/export',
  
  createEntity: createApprovalDelegation,
  searchEntity: searchApprovalDelegation,
  editEntity: editApprovalDelegation,
  
  editUpdates: {
    reason: 'Updated Reason Test',
    status: 'Inactive',
  },
  
  sortableColumns: ['Delegator', 'Delegate', 'Approvable Type', 'Start Date', 'End Date', 'Status'],
  
  viewType: 'dialog',
  viewDialogTitle: 'Approval Delegation Details',
  
  expectedExportColumns: ['ID', 'Delegator', 'Delegate', 'Approvable Type', 'Start Date', 'End Date', 'Reason', 'Status', 'Created At'],
  
  filterTests: [
    {
        filterName: 'Status',
        filterType: 'select',
        filterValue: 'Active',
        expectedText: 'Active',
    }
  ]
});
