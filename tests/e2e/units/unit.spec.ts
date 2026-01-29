import { runSimpleCrudE2ETests } from '../simple-crud-tests';
import { createUnit, searchUnit, editUnit } from '../helpers';

runSimpleCrudE2ETests({
  entityName: 'Unit',
  entityNamePlural: 'Units',
  route: '/units',
  searchPlaceholder: 'Search units...',
  createEntity: createUnit,
  searchEntity: searchUnit,
  editEntity: editUnit,
});
