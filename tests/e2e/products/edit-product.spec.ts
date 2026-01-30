import { test, expect } from '@playwright/test';
import { createProduct, editProduct, searchProduct } from '../helpers';

test('edit existing product end-to-end', async ({ page }) => {
  // 1. Create a product to edit
  const productCode = await createProduct(page, {
    name: 'Product to Edit',
    selling_price: '1000',
  });

  // 2. Edit the product
  const newName = 'Product Edited Name';
  const newPrice = '1200';
  await editProduct(page, productCode, {
    name: newName,
    selling_price: newPrice,
    status: 'Inactive',
  });

  // 3. Verify changes in the table
  await searchProduct(page, productCode);
  const row = page.locator('tr', { hasText: productCode });
  await expect(row).toContainText(newName);
  await expect(row).toContainText('1,200.00');
  await expect(row).toContainText('Inactive');
});
