import { generateModuleTests } from '../shared-test-factories';
import { createAsset, searchAsset } from './helpers';

generateModuleTests({
  entityName: 'Asset',
  entityNamePlural: 'Assets',
  route: '/assets',
  apiPath: '/api/assets',
  exportApiPath: '/api/assets/export',
  
  createEntity: createAsset,
  searchEntity: searchAsset,
  
  // View is a page, not a dialog
  viewType: 'page',
  viewUrlPattern: /\/assets\/\w+/,
  
  sortableColumns: [
    'Code', 
    'Name', 
    'Category', 
    'Branch', 
    'Status', 
    'Cost', 
    'Purchase Date'
  ],
  
  expectedExportColumns: [
    'ID', 'Asset Code', 'Name', 'Category', 'Model', 'Serial Number', 'Barcode',
    'Branch', 'Location', 'Department', 'Employee', 'Supplier',
    'Purchase Date', 'Purchase Cost', 'Currency', 'Warranty End Date',
    'Status', 'Condition', 'Depreciation Method', 'Useful Life (Months)', 'Created At'
  ],

  filterTests: [
      {
          filterName: 'Status',
          filterType: 'select',
          filterValue: 'active',
          expectedText: 'active'
      },
      // AsyncSelect filters might be tricky with standard GenerateModuleTests if it expects simple Select.
      // Shared test factories supports 'combobox' which handles AsyncSelect/Combobox.
      {
          filterName: 'Branch',
          filterType: 'combobox',
          filterValue: 'Head Office',
          expectedText: 'Head Office'
      }
  ]
});
