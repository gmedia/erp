import { test, expect } from '@playwright/test';
import { login, createJournalEntry } from '../helpers';

test.describe('Posting Journal End-to-End', () => {
  test.setTimeout(120000);

  test('should bulk post draft journal entries', async ({ page }) => {
    // 1. Login
    await login(page);

    // 2. Create a draft journal entry to ensure we have data
    const timestamp = Date.now();
    const reference = `E2E-POST-${timestamp}`;
    const description = `E2E Bulk Post Test ${timestamp}`;
    
    await createJournalEntry(page, {
      reference,
      description,
      lines: [
        { account: 'Cash in Banks', debit: '1500', credit: '0', memo: 'Debit' },
        { account: 'Sales Revenue', debit: '0', credit: '1500', memo: 'Credit' },
      ]
    });

    // 3. Navigate to Posting Journals page
    await page.goto('/posting-journals');
    await page.waitForLoadState('networkidle');

    // 4. Search for the entry to ensure it's visible and handles pagination
    const searchInput = page.getByPlaceholder(/Search journals\.\.\./i);
    await searchInput.fill(description);
    await page.waitForTimeout(1000); // Wait for debounce if any, or just network load
    await page.waitForLoadState('networkidle');

    // 5. Verify the entry is in the list (using description as it's visible)
    const row = page.locator('tr').filter({ hasText: description });
    await expect(row).toBeVisible();

    // 6. Select the entry
    const checkbox = row.locator('button[role="checkbox"]');
    await checkbox.click();

    // 7. Click Post Selected
    const postButton = page.getByRole('button', { name: /Post Selected/i });
    await expect(postButton).toBeEnabled();
    await postButton.click();

    // 8. Verify the entry is gone from the list
    await expect(row).not.toBeVisible({ timeout: 15000 });
  });

  test('should handle "Select All" functionality', async ({ page }) => {
    await login(page);
    await page.goto('/posting-journals');
    await page.waitForLoadState('networkidle');

    // Check if there are journals to test with
    const rows = page.locator('tbody tr');
    const count = await rows.count();
    
    // If no journals, we might need to create some or skip
    if (count > 0 && !(await page.getByText('No draft journals found').isVisible())) {
        // Find the "Select All" checkbox in the header
        const selectAllCheckbox = page.locator('thead th').locator('button[role="checkbox"]');
        await selectAllCheckbox.click();

        // Verify all rows are selected
        const rowCheckboxes = page.locator('tbody tr').locator('button[role="checkbox"]');
        const checkboxCount = await rowCheckboxes.count();
        for (let i = 0; i < checkboxCount; i++) {
            await expect(rowCheckboxes.nth(i)).toHaveAttribute('aria-checked', 'true');
        }
        
        // Verify button text updates
        const postButton = page.getByRole('button', { name: /Post Selected/i });
        await expect(postButton).toContainText(`(${checkboxCount})`);
    } else {
        console.log('Skipping "Select All" test as no draft journals are available.');
    }
  });
});
