import { test, expect } from '@playwright/test';
import { createSupplier, searchSupplier } from '../helpers';

test('view supplier details', async ({ page }) => {
  // Create a supplier first
  const email = await createSupplier(page, { name: 'View Test Supplier' });

  // Search and view
  await searchSupplier(page, email);

  // Open view modal
  const row = page.locator('tr', { hasText: email }).first();
  await expect(row).toBeVisible();
  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await actionsBtn.click();
  
  const viewItem = page.getByRole('menuitem', { name: /View/i });
  await viewItem.click();

  // Verify modal content
  const dialog = page.getByRole('dialog');
  await expect(dialog.getByText('Supplier Details')).toBeVisible(); // Title changed to Supplier Details
  await expect(dialog.getByText('View Test Supplier')).toBeVisible();
  await expect(dialog.getByText(email)).toBeVisible();
});
