import { test, expect } from '@playwright/test';
import { createProduct, deleteProduct, searchProduct } from '../helpers';

test('delete existing product end-to-end', async ({ page }) => {
  // 1. Create a product to delete
  const productCode = await createProduct(page, {
    name: 'Product to Delete',
  });

  // 2. Delete the product
  await deleteProduct(page, productCode);

  // 3. Verify it's gone from the search
  await page.fill('input[placeholder="Search code, name..."]', productCode);
  await page.press('input[placeholder="Search code, name..."]', 'Enter');
  
  // Table should show "No results."
  await expect(page.locator('text=No results.')).toBeVisible();
});
