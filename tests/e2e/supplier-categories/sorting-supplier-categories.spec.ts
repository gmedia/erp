import { test, expect } from '@playwright/test';
import { createSupplierCategory, login } from '../helpers';

test.describe('Supplier Category Management - Sorting', () => {
  test('should sort supplier categories by name', async ({ page }) => {
    await login(page);
    await page.goto('/supplier-categories');

    const nameHeader = page.getByRole('button', { name: /Name/i });
    await expect(nameHeader).toBeVisible();
    
    await nameHeader.click();
    await page.waitForLoadState('networkidle');
    
    await nameHeader.click();
    await page.waitForLoadState('networkidle');
  });

  test('should sort supplier categories by Created At', async ({ page }) => {
    await login(page);
    await page.goto('/supplier-categories');

    const createdHeader = page.getByRole('button', { name: /Created At/i });
    await expect(createdHeader).toBeVisible();
    await createdHeader.click();
    await page.waitForLoadState('networkidle');
  });

  test('should sort supplier categories by Updated At', async ({ page }) => {
    await login(page);
    await page.goto('/supplier-categories');

    const updatedHeader = page.getByRole('button', { name: /Updated At/i });
    await expect(updatedHeader).toBeVisible();
    await updatedHeader.click();
    await page.waitForLoadState('networkidle');
  });
});
