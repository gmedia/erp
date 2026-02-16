import { generateModuleTests } from '../shared-test-factories';
import {
  createAssetLocation,
  searchAssetLocation,
  editAssetLocation,
  deleteAssetLocation,
} from './helpers';

generateModuleTests({
  entityName: 'Asset Location',
  entityNamePlural: 'Asset Locations',
  route: '/asset-locations',
  apiPath: '/api/asset-locations',
  
  // Callbacks
  createEntity: createAssetLocation,
  searchEntity: searchAssetLocation,
  editEntity: editAssetLocation,
  editUpdates: {
    name: `Updated Location ${Date.now()}`,
    code: `UPD-LOC-${Date.now()}`,
  },

  // DataTable config
  sortableColumns: ['Code', 'Name', 'Branch', 'Parent Location'],
  
  // View config
  viewType: 'dialog',
  viewDialogTitle: 'Asset Location Details',

  // Export config
  exportApiPath: '/api/asset-locations/export',
  expectedExportColumns: ['ID', 'Code', 'Name', 'Branch', 'Parent Location', 'Created At'],

  // Filter config
  filterTests: [
    {
      filterName: 'Branch',
      filterType: 'combobox',
      filterValue: 'Branch 1', 
      expectedText: 'Branch 1',
    }
  ]
});
