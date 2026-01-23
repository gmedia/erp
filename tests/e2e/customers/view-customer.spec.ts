import { test, expect } from '@playwright/test';
import { createCustomer, searchCustomer } from '../helpers';

test('view customer details', async ({ page }) => {
  // Create a customer first
  const email = await createCustomer(page, { name: 'View Test Customer' });

  // Search and view
  await searchCustomer(page, email);

  // Open view modal
  const row = page.locator('tr', { hasText: email }).first();
  await expect(row).toBeVisible();
  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await actionsBtn.click();
  
  const viewItem = page.getByRole('menuitem', { name: /View/i });
  await viewItem.click();

  // Verify modal content
  const dialog = page.getByRole('dialog');
  await expect(dialog.getByText('View Customer')).toBeVisible();
  await expect(dialog.getByText('View Test Customer')).toBeVisible();
  await expect(dialog.getByText(email)).toBeVisible();
});
