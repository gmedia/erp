import { test, expect } from '@playwright/test';
import { createCustomer, searchCustomer } from '../helpers';

test('delete customer', async ({ page }) => {
  // Create a customer first
  const email = await createCustomer(page, { name: 'Delete Test Customer' });

  // Search for the customer
  await searchCustomer(page, email);

  // Open actions menu and click delete
  const row = page.locator('tr', { hasText: email }).first();
  await expect(row).toBeVisible();
  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await actionsBtn.click();
  
  const deleteItem = page.getByRole('menuitem', { name: /Delete/i });
  await deleteItem.click();

  // Confirm deletion
  const confirmBtn = page.getByRole('button', { name: /Delete/i });
  await confirmBtn.click();

  // Verify the customer is removed (search should not find it)
  await page.fill('input[placeholder="Search customers..."]', email);
  await page.press('input[placeholder="Search customers..."]', 'Enter');
  
  // Wait a moment for the search to execute
  await page.waitForTimeout(1000);
  
  // Verify customer is no longer in the table
  await expect(page.locator(`text=${email}`)).not.toBeVisible();
});
