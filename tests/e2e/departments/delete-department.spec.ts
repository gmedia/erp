import { test, expect } from '@playwright/test';
import { createDepartment, deleteDepartment } from '../helpers';

test('delete department', async ({ page }) => {
  const name = await createDepartment(page, { name: 'To Delete Department' });

  await deleteDepartment(page, name);

  await expect(page.locator(`text=${name}`)).not.toBeVisible();
});
