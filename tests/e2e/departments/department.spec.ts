import { runSimpleCrudE2ETests } from '../simple-crud-tests';
import { createDepartment, searchDepartment, editDepartment } from '../helpers';

runSimpleCrudE2ETests({
  entityName: 'department',
  entityNamePlural: 'departments',
  route: '/departments',
  searchPlaceholder: 'Search departments...',
  createEntity: createDepartment,
  searchEntity: searchDepartment,
  editEntity: editDepartment,
});
