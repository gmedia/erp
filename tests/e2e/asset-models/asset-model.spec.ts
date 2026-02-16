import { generateModuleTests } from '../shared-test-factories';
import { 
  createAssetModel, 
  searchAssetModel, 
  editAssetModel, 
  deleteAssetModel 
} from './helpers';

generateModuleTests({
  entityName: 'Asset Model',
  entityNamePlural: 'Asset Models',
  route: '/asset-models',
  apiPath: '/api/asset-models',
  
  createEntity: async (page) => {
    return await createAssetModel(page);
  },
  searchEntity: (page, identifier) => searchAssetModel(page, identifier),
  editEntity: (page, identifier, updates) => editAssetModel(page, identifier, updates),
  editUpdates: { model_name: 'UPDATED-MODEL-NAME' },

  // DataTable
  sortableColumns: ['Model Name', 'Manufacturer', 'Category'],
  
  // View
  viewType: 'dialog',
  viewDialogTitle: 'Asset Model Details',

  // Export
  exportApiPath: '/api/asset-models/export',
  expectedExportColumns: ['ID', 'Model Name', 'Manufacturer', 'Category', 'Specs', 'Created At'],
});
