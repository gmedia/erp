import { test, expect } from '@playwright/test';
import { createUnit, searchUnit, login } from '../helpers';

test.describe('Unit Management - Search', () => {
  test('should search for a unit by name', async ({ page }) => {
    const name1 = await createUnit(page);
    const name2 = await createUnit(page);

    await searchUnit(page, name1);
    await expect(page.locator(`tr:has-text("${name1}")`)).toBeVisible();
    await expect(page.locator(`tr:has-text("${name2}")`)).not.toBeVisible();

    await searchUnit(page, name2);
    await expect(page.locator(`tr:has-text("${name2}")`)).toBeVisible();
    await expect(page.locator(`tr:has-text("${name1}")`)).not.toBeVisible();
  });
});
