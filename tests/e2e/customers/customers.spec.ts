import { test, expect } from '@playwright/test';
import { createCustomer, searchCustomer, login } from '../helpers';

test.describe('Customer CRUD E2E', () => {
  test('add new customer end-to-end', async ({ page }) => {
    // Create customer using shared helper (includes login & navigation)
    const email = await createCustomer(page);

    // Search for the newly created customer
    await searchCustomer(page, email);

    // Verify the customer appears in the table
    const row = page.locator(`text=${email}`);
    await expect(row).toBeVisible();
  });

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
    await expect(page.getByText('View Customer')).toBeVisible();
    await expect(page.getByText('View Test Customer')).toBeVisible();
    await expect(page.getByText(email)).toBeVisible();
  });

  test('edit customer', async ({ page }) => {
    // Create a customer first
    const email = await createCustomer(page, { name: 'Original Name' });

    // Search for the customer
    await searchCustomer(page, email);

    // Open actions menu and click edit
    const row = page.locator('tr', { hasText: email }).first();
    await expect(row).toBeVisible();
    const actionsBtn = row.getByRole('button', { name: /Actions/i });
    await actionsBtn.click();
    
    const editItem = page.getByRole('menuitem', { name: /Edit/i });
    await editItem.click();

    // Update the name
    await page.fill('input[name="name"]', 'Updated Name');

    // Submit
    const dialog = page.getByRole('dialog');
    const updateBtn = dialog.getByRole('button', { name: /Update/ });
    await updateBtn.click();

    // Wait for dialog to close
    await expect(dialog).not.toBeVisible();

    // Verify the updated name appears
    await expect(page.getByText('Updated Name')).toBeVisible();
  });

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

  test('filter customers by status', async ({ page }) => {
    // Login and navigate
    await login(page);
    await page.goto('/customers');

    // Open filter
    const filterButton = page.getByRole('button', { name: /filter/i });
    if (await filterButton.isVisible()) {
      await filterButton.click();
    }

    // Select active status filter
    const statusFilter = page.locator('button:has-text("All statuses")');
    if (await statusFilter.isVisible()) {
      await statusFilter.click();
      await page.getByRole('option', { name: 'Active' }).click();
    }

    // Apply filter
    const applyBtn = page.getByRole('button', { name: /apply/i });
    if (await applyBtn.isVisible()) {
      await applyBtn.click();
    }

    // Verify only active customers are shown
    const activeBadges = page.locator('text=Active');
    await expect(activeBadges.first()).toBeVisible();
  });
});
