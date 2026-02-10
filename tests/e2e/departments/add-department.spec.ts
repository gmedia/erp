import { test, expect } from '@playwright/test';
import { createDepartment, searchDepartment } from '../helpers';

test('add new department end-to-end', async ({ page }) => {
  const name = await createDepartment(page);

  await searchDepartment(page, name);

  await expect(page.locator(`text=${name}`)).toBeVisible();
});
