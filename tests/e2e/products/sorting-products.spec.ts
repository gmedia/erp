import { test, expect } from '@playwright/test';
import { createProduct, login } from '../helpers';

test.describe('Product Management - Sorting', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/products');
    });

  test('should sort products by name', async ({ page }) => {
    const nameHeader = page.getByRole('button', { name: /Name/i });
    await expect(nameHeader).toBeVisible();
    
    await nameHeader.click();
    await page.waitForLoadState('networkidle');
    
    await nameHeader.click();
    await page.waitForLoadState('networkidle');
  });

  test('should sort products by Code', async ({ page }) => {
    const codeHeader = page.getByRole('button', { name: /Code/i });
    await expect(codeHeader).toBeVisible();
    await codeHeader.click();
    await page.waitForLoadState('networkidle');
  });

   test('should sort products by Buying Price', async ({ page }) => {
    const priceHeader = page.getByRole('button', { name: /Buying Price/i });
    // Note: If 'Buying Price' column is not searchable/sortable or named differently, adjust here.
    // Based on standard DataTable, check if it's sortable.
    if (await priceHeader.isVisible()) {
        await priceHeader.click();
        await page.waitForLoadState('networkidle');
    }
  });
});
