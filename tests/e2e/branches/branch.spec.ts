import { runSimpleCrudE2ETests } from '../simple-crud-tests';
import { createBranch, searchBranch, editBranch } from '../helpers';

runSimpleCrudE2ETests({
  entityName: 'branch',
  entityNamePlural: 'branches',
  route: '/branches',
  searchPlaceholder: 'Search branches...',
  createEntity: createBranch,
  searchEntity: searchBranch,
  editEntity: editBranch,
});
