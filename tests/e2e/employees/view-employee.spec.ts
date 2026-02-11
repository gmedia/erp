import { test, expect } from '@playwright/test';
import { login, createEmployee, searchEmployee } from '../helpers';

test('view employee details in modal', async ({ page }) => {
  await login(page);
  
  const email = await createEmployee(page);
  await searchEmployee(page, email);
  
  const row = page.locator('tr', { hasText: email }).first();
  await expect(row).toBeVisible();
  
  // Open Actions menu
  const actionsButton = row.getByRole('button', { name: /Actions/i });
  await actionsButton.click();
  
  // Click View
  const viewMenuItem = page.getByRole('menuitem', { name: /View/i });
  await viewMenuItem.click();
  
  // Verify modal is visible
  const modal = page.getByRole('dialog');
  await expect(modal).toBeVisible();
  
  // Verify employee details in modal
  // Assuming the modal shows the email or name
  await expect(modal).toContainText(email);
  
  // Close modal
  await page.keyboard.press('Escape');
  await expect(modal).toBeHidden();
});
