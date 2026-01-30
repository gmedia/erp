import { test, expect } from '@playwright/test';
import { createProduct, searchProduct } from '../helpers';

test('add new product end-to-end', async ({ page }) => {
  // Create product using shared helper
  const productCode = await createProduct(page, {
    name: 'E2E Test Product',
    type: 'Finished Good',
    category_id: 'Electronics',
    unit_id: 'Piece',
    cost: '1000',
    selling_price: '2000',
  });

  // Search for the newly created product
  await searchProduct(page, productCode);

  // Verify the product appears in the table
  const row = page.locator('tr', { hasText: productCode });
  await expect(row).toBeVisible();
  await expect(row).toContainText('E2E Test Product');
});

test('add new service end-to-end', async ({ page }) => {
  // Create service using shared helper
  const productCode = await createProduct(page, {
    name: 'E2E Test Service',
    type: 'Service',
    category_id: 'Electronics',
    unit_id: 'Piece',
    cost: '0',
    selling_price: '500',
    billing_model: 'Subscription',
    is_recurring: true,
  });

  // Search for the newly created service
  await searchProduct(page, productCode);

  // Verify the service appears in the table
  const row = page.locator('tr', { hasText: productCode });
  await expect(row).toBeVisible();
  await expect(row).toContainText('E2E Test Service');
  await expect(row).toContainText('Service');
});
