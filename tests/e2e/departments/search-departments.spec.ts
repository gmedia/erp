import { test, expect } from '@playwright/test';
import { createDepartment, searchDepartment } from '../helpers';

test('search departments by name', async ({ page }) => {
  const name = await createDepartment(page);
  await page.goto('/departments');

  await searchDepartment(page, name);
  await expect(page.locator(`text=${name}`)).toBeVisible();
});
