import { test, expect } from '@playwright/test';
import { createDepartment, searchDepartment } from '../helpers';

test('edit department', async ({ page }) => {
  const name = await createDepartment(page);

  await searchDepartment(page, name);

  const row = page.locator('tr', { hasText: name }).first();
  await row.getByRole('button', { name: /Actions/i }).click();
  await page.getByRole('menuitem', { name: /Edit/i }).click();

  const updatedName = `Updated Dept ${Date.now()}`;
  await page.fill('input[name="name"]', updatedName);
  
  await Promise.all([
    page.waitForResponse(resp => resp.url().includes('/api/departments') && (resp.status() === 200 || resp.status() === 422)),
    page.getByRole('button', { name: /Submit|Update|Save/i }).click(),
  ]);

  await page.goto('/departments');
  await searchDepartment(page, updatedName);
  await expect(page.getByText(updatedName)).toBeVisible();
});
