import { generateModuleTests } from '../shared-test-factories';
import {
    createAssetStocktake,
    editAssetStocktake,
    searchAssetStocktake,
} from './helpers';

generateModuleTests({
    entityName: 'Asset Stocktake',
    entityNamePlural: 'Asset Stocktakes',
    route: '/asset-stocktakes',
    apiPath: '/api/asset-stocktakes',
    createEntity: async (page) => {
        return createAssetStocktake(page);
    },
    searchEntity: async (page, identifier) => {
        return searchAssetStocktake(page, identifier);
    },
    editEntity: async (page, identifier, updates) => {
        return editAssetStocktake(page, identifier, updates);
    },
    editUpdates: { reference: `UPDATED-ST-${Date.now()}` },
    sortableColumns: ['Reference', 'Branch', 'Planned Date', 'Performed Date', 'Status', 'Created By'],
    viewType: 'dialog',
    exportApiPath: '/api/asset-stocktakes/export',
    expectedExportColumns: ['ID', 'Reference', 'Branch', 'Planned At', 'Performed At', 'Status', 'Created By', 'Created At'],
});
