import { generateModuleTests } from '../shared-test-factories';
import { createAssetMovement, searchAssetMovement, deleteAssetMovement } from './helpers';

generateModuleTests({
  entityName: 'Asset Movement',
  entityNamePlural: 'Asset Movements',
  route: '/asset-movements',
  apiPath: '/api/asset-movements',
  exportApiPath: '/api/asset-movements/export',
  
  createEntity: createAssetMovement,
  searchEntity: searchAssetMovement,
  
  // View opens a dialog
  viewType: 'dialog',
  
  sortableColumns: [
    'Asset',
    'Type',
    'Date',
    'Ref/Notes',
    'PIC'
  ],
  
  expectedExportColumns: [
    'ID', 'Asset Code', 'Asset Name', 'Type', 'Date',
    'Origin Branch', 'Destination Branch',
    'Origin Location', 'Destination Location',
    'Origin Department', 'Destination Department',
    'Origin Employee', 'Destination Employee',
    'Reference', 'Notes', 'Recorded By', 'Created At'
  ],

  filterTests: [
      {
          filterName: 'Type',
          filterType: 'select',
          filterValue: 'transfer',
          expectedText: 'transfer'
      }
      // Asset filter (async) skipped to avoid dependency on specific seeded data
  ]
});
