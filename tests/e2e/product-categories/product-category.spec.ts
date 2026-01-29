import { runSimpleCrudE2ETests } from '../simple-crud-tests';
import {
  createProductCategory,
  searchProductCategory,
  editProductCategory,
} from '../helpers';

runSimpleCrudE2ETests({
  entityName: 'Product Category',
  entityNamePlural: 'Product Categories',
  route: '/product-categories',
  searchPlaceholder: 'Search product categories...',
  createEntity: createProductCategory,
  searchEntity: searchProductCategory,
  editEntity: editProductCategory,
});
