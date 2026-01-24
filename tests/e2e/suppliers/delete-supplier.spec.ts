import { test, expect } from '@playwright/test';
import { createSupplier, deleteSupplier } from '../helpers';

test('delete supplier', async ({ page }) => {
  // Create a supplier first
  const email = await createSupplier(page, { name: 'To Delete Supplier' });

  // Delete it
  await deleteSupplier(page, email);

  // Verify it's gone
  await expect(page.locator(`text=${email}`)).not.toBeVisible();
});
