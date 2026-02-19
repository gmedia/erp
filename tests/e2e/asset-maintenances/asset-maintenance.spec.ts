import { generateModuleTests } from '../shared-test-factories';
import { createAssetMaintenance, searchAssetMaintenance } from './helpers';

generateModuleTests({
  entityName: 'Asset Maintenance',
  entityNamePlural: 'Asset Maintenances',
  route: '/asset-maintenances',
  apiPath: '/api/asset-maintenances',
  exportApiPath: '/api/asset-maintenances/export',

  createEntity: createAssetMaintenance,
  searchEntity: searchAssetMaintenance,

  viewType: 'dialog',

  sortableColumns: [
    'Asset',
    'Type',
    'Status',
    'Scheduled',
    'Performed',
    'Supplier',
    'Notes',
    'Cost',
  ],

  expectedExportColumns: [
    'ID',
    'Asset Code',
    'Asset Name',
    'Maintenance Type',
    'Status',
    'Scheduled At',
    'Performed At',
    'Supplier',
    'Cost',
    'Notes',
    'Created By',
    'Created At',
  ],

  filterTests: [
    {
      filterName: 'Type',
      filterType: 'select',
      filterValue: 'preventive',
      expectedText: 'preventive',
    },
    {
      filterName: 'Status',
      filterType: 'select',
      filterValue: 'scheduled',
      expectedText: 'scheduled',
    },
  ],
});
