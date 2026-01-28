import { runSimpleCrudE2ETests } from '../simple-crud-tests';
import { createPosition, searchPosition, editPosition } from '../helpers';

runSimpleCrudE2ETests({
  entityName: 'position',
  entityNamePlural: 'positions',
  route: '/positions',
  searchPlaceholder: 'Search positions...',
  createEntity: createPosition,
  searchEntity: searchPosition,
  editEntity: editPosition,
});
