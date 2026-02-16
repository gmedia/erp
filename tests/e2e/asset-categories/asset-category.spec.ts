import { generateModuleTests } from '../shared-test-factories';
import { 
  createAssetCategory, 
  searchAssetCategory, 
  editAssetCategory, 
  deleteAssetCategory 
} from './helpers';

generateModuleTests({
  entityName: 'Asset Category',
  entityNamePlural: 'Asset Categories',
  route: '/asset-categories',
  apiPath: '/api/asset-categories',
  
  createEntity: async (page) => {
    const { code } = await createAssetCategory(page);
    return code;
  },
  searchEntity: (page, identifier) => searchAssetCategory(page, identifier),
  editEntity: (page, identifier, updates) => editAssetCategory(page, identifier, updates),
  editUpdates: { name: 'UPDATED-NAME' },

  // DataTable
  sortableColumns: ['Code', 'Name', 'Default Useful Life (Months)', 'Created At', 'Updated At'],
  
  // View
  viewType: 'dialog',
  viewDialogTitle: 'Asset Category Details',

  // Export
  exportApiPath: '/api/asset-categories/export',
  expectedExportColumns: ['Code', 'Name', 'Default Useful Life (Months)', 'Created At', 'Updated At'],
});
