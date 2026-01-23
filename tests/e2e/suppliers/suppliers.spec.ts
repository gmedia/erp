import { test, expect } from '@playwright/test';
import { createSupplier, searchSupplier, editSupplier, deleteSupplier } from '../helpers';

test('supplier CRUD flow', async ({ page }) => {
  // 1. Create Supplier
  const email = await createSupplier(page, {
    name: 'End2End Supplier',
    category: 'Electronics',
    status: 'Active'
  });

  // 2. Search and Verify
  await searchSupplier(page, email);
  const row = page.locator(`tr:has-text("${email}")`);
  await expect(row).toBeVisible();
  
  // Verify columns with capitalized values
  await expect(row).toContainText('End2End Supplier');
  await expect(row).toContainText('Head Office');
  await expect(row).toContainText('Electronics');
  await expect(row).toContainText('Active');

  // 3. Edit Supplier
  await editSupplier(page, email, {
    name: 'Updated E2E Supplier',
    status: 'Inactive'
  });
  
  // Verify Update
  await searchSupplier(page, email);
  const updatedRow = page.locator(`tr:has-text("${email}")`);
  await expect(updatedRow).toContainText('Updated E2E Supplier');
  await expect(updatedRow).toContainText('Inactive');

  // 4. Delete Supplier
  await deleteSupplier(page, email);
});
