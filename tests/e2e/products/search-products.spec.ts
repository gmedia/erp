import { test, expect } from '@playwright/test';
import { createProduct, searchProduct, login } from '../helpers';

test.describe('Product Management - Search', () => {
  test('should search for a product by name', async ({ page }) => {
    const code1 = await createProduct(page, { name: 'Searchable Product 1', category_id: 'Electronics', unit_id: 'Piece', cost: '10', selling_price: '20' });
    const code2 = await createProduct(page, { name: 'Searchable Product 2', category_id: 'Electronics', unit_id: 'Piece', cost: '10', selling_price: '20' });

    await searchProduct(page, code1);
    await expect(page.locator(`tr:has-text("${code1}")`)).toBeVisible();
    await expect(page.locator(`tr:has-text("${code2}")`)).not.toBeVisible();

    await searchProduct(page, code2);
    await expect(page.locator(`tr:has-text("${code2}")`)).toBeVisible();
    await expect(page.locator(`tr:has-text("${code1}")`)).not.toBeVisible();
  });
});
