import { runSimpleCrudE2ETests } from '../simple-crud-tests';
import { createCustomerCategory, searchCustomerCategory, editCustomerCategory } from '../helpers';

runSimpleCrudE2ETests({
  entityName: 'customer category',
  entityNamePlural: 'customer categories',
  route: '/customer-categories',
  searchPlaceholder: 'Search customer categories...',
  createEntity: createCustomerCategory,
  searchEntity: searchCustomerCategory,
  editEntity: editCustomerCategory,
});
