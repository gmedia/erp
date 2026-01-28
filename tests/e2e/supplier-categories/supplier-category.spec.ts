import { runSimpleCrudE2ETests } from '../simple-crud-tests';
import { createSupplierCategory, searchSupplierCategory, editSupplierCategory } from '../helpers';

runSimpleCrudE2ETests({
  entityName: 'supplier category',
  entityNamePlural: 'supplier categories',
  route: '/supplier-categories',
  searchPlaceholder: 'Search supplier categories...',
  createEntity: createSupplierCategory,
  searchEntity: searchSupplierCategory,
  editEntity: editSupplierCategory,
});
