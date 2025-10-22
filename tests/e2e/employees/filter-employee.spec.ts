import { test, expect } from '@playwright/test';
import { login, createEmployee, searchEmployee } from '../helpers';

test('filter employees end‑to‑end', async ({ page }) => {
  // Log in
  await login(page);

  // Create employees with distinct department/position combos
  const emp1 = await createEmployee(page, {
    department: 'Engineering',
    position: 'Manager',
  });
  const emp2 = await createEmployee(page, {
    department: 'Marketing',
    position: 'Senior Developer',
  });
  const emp3 = await createEmployee(page, {
    department: 'Engineering',
    position: 'Senior Developer',
  });

  // Open filter dialog
  const filtersBtn = page.getByRole('button', { name: /Filters/i });
  await expect(filtersBtn).toBeVisible();
  await filtersBtn.click();

  // Set Department filter to Engineering
  await page.click('button:has-text("Select a department")');
  await page.getByRole('option', { name: 'Engineering' }).click();

  // Set Position filter to Manager
  await page.click('button:has-text("Select a position")');
  await page.getByRole('option', { name: 'Manager' }).click();

  // Apply filters
  const applyBtn = page.getByRole('dialog').getByRole('button', { name: /Apply Filters/i });
  await expect(applyBtn).toBeVisible();
  await applyBtn.click();
  await page.waitForLoadState('networkidle');

  // Verify only matching employee is visible
  await expect(page.locator(`text=${emp1}`)).toBeVisible();
  await expect(page.locator(`text=${emp2}`)).toBeHidden();
  await expect(page.locator(`text=${emp3}`)).toBeHidden();

  // Test search filter with partial email of emp3
  const partial = emp3.split('@')[0].slice(0, 5);
  await page.fill('input[placeholder="Search employees..."]', partial);
  await page.press('input[placeholder="Search employees..."]', 'Enter');
  await page.waitForLoadState('networkidle');

  // Verify search results
  await expect(page.locator(`text=${emp3}`)).toBeVisible();
});