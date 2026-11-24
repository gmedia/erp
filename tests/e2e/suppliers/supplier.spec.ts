import { generateModuleTests } from '../shared-test-factories';
import { createSupplier, searchSupplier, editSupplier } from './helpers';

generateModuleTests({
  entityName: 'Supplier',
  entityNamePlural: 'Suppliers',
  route: '/suppliers',
  apiPath: '/api/suppliers',
  exportApiPath: '/api/suppliers/export',
  
  createEntity: createSupplier,
  searchEntity: searchSupplier,
  editEntity: editSupplier,
  
  editUpdates: {
    name: 'Updated Supplier Name',
    status: 'Inactive',
  },
  
  sortableColumns: ['Name', 'Email', 'Phone', 'Branch', 'Category', 'Status'],
  
  viewType: 'dialog',
  viewDialogTitle: 'Supplier Details',
  
  expectedExportColumns: ['ID', 'Name', 'Email', 'Phone', 'Address', 'Branch', 'Category', 'Status', 'Created At'],
  
  filterTests: [
    {
        filterName: 'Branch',
        filterType: 'combobox',
        filterValue: 'Kantor Pusat',
        expectedText: 'Kantor Pusat',
    },
    {
        filterName: 'Category',
        filterType: 'combobox',
        filterValue: 'Perlengkapan Kantor',
        expectedText: 'Perlengkapan Kantor',
    },
    {
        filterName: 'Status',
        filterType: 'select',
        filterValue: 'Active',
        expectedText: 'Active',
    }
  ]
});
