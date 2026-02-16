import { generateModuleTests } from '../shared-test-factories';
import { createCustomer, searchCustomer, editCustomer } from './helpers';

generateModuleTests({
  entityName: 'Customer',
  entityNamePlural: 'Customers',
  route: '/customers',
  apiPath: '/api/customers',
  exportApiPath: '/api/customers/export',
  
  createEntity: createCustomer,
  searchEntity: searchCustomer,
  editEntity: editCustomer,
  
  editUpdates: {
    name: 'Updated Customer Name',
    status: 'Inactive',
  },
  
  sortableColumns: ['Name', 'Email', 'Phone', 'Branch', 'Category', 'Status'],
  
  viewType: 'dialog',
  viewDialogTitle: 'View Customer',
  
  expectedExportColumns: ['Name', 'Email', 'Phone', 'Branch', 'Category', 'Status'],
  
  filterTests: [
    {
        filterName: 'Branch',
        filterType: 'combobox',
        filterValue: 'Head Office',
        expectedText: 'Head Office',
    },
    {
        filterName: 'Category',
        filterType: 'combobox',
        filterValue: 'Retail',
        expectedText: 'Retail',
    },
    {
        filterName: 'Status',
        filterType: 'select',
        filterValue: 'Active',
        expectedText: 'Active',
    }
  ]
});
