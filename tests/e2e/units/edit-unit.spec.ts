import { test, expect } from '@playwright/test';
import { createUnit, editUnit, searchUnit, login } from '../helpers';

test.describe('Unit Management - Edit', () => {
  test('should edit an existing unit successfully', async ({ page }) => {
    const oldName = await createUnit(page);
    const newName = `${oldName} Updated`;

    await editUnit(page, oldName, { name: newName });

    await searchUnit(page, newName);
    await expect(page.locator(`tr:has-text("${newName}")`)).toBeVisible();
  });
});
