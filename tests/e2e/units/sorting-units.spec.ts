import { test, expect } from '@playwright/test';
import { createUnit, login } from '../helpers';

test.describe('Unit Management - Sorting', () => {
  test('should sort units by name', async ({ page }) => {
    await login(page);
    await page.goto('/units');

    const nameHeader = page.getByRole('button', { name: /Name/i });
    await expect(nameHeader).toBeVisible();
    
    await nameHeader.click();
    await page.waitForLoadState('networkidle');
    
    await nameHeader.click();
    await page.waitForLoadState('networkidle');
  });

  test('should sort units by Created At', async ({ page }) => {
    await login(page);
    await page.goto('/units');

    const createdHeader = page.getByRole('button', { name: /Created At/i });
    await expect(createdHeader).toBeVisible();
    await createdHeader.click();
    await page.waitForLoadState('networkidle');
  });

  test('should sort units by Updated At', async ({ page }) => {
    await login(page);
    await page.goto('/units');

    const updatedHeader = page.getByRole('button', { name: /Updated At/i });
    await expect(updatedHeader).toBeVisible();
    await updatedHeader.click();
    await page.waitForLoadState('networkidle');
  });
});
