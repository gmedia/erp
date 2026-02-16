import { Page, expect } from '@playwright/test';
import { login } from '../helpers';

/**
 * Create a new journal entry via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override the default values.
 * @returns The unique reference used for the created journal entry.
 */
export async function createJournalEntry(
  page: Page,
  overrides: Partial<{
    entry_date: string; // YYYY-MM-DD
    reference: string;
    description: string;
    lines: Array<{
      account: string;
      debit: string;
      credit: string;
      memo?: string;
    }>
  }> = {}
): Promise<string> {
  const timestamp = Date.now();
  const random = Math.floor(Math.random() * 10000);
  const defaultRef = `REF-${timestamp}-${random}`;
  const defaultDesc = `Journal Entry ${timestamp}`;

  // 1️⃣ Login
  await login(page);

  // 2️⃣ Navigate to Journal Entries page
  await page.goto('/journal-entries');

  // 3️⃣ Open the "Add Journal Entry" dialog
  const addButton = page.getByRole('button', { name: /Add/i });
  await expect(addButton).toBeVisible();
  await addButton.click();

  // Use specific name to avoid matching popovers/selects
  const dialog = page.getByRole('dialog', { name: /Add|New|Edit|Create/i }).first();
  await expect(dialog).toBeVisible();

  // 4️⃣ Fill Header
  const reference = overrides.reference ?? defaultRef;
  const description = overrides.description ?? defaultDesc;

  if (overrides.entry_date) {
    const dateBtn = dialog.locator('button').filter({ hasText: /Pick a date/i }).first();
    if (await dateBtn.isVisible()) {
        await dateBtn.click();
        // Assuming we just pick today or similar for simplicity if not implementing full date picker logic
    }
  }
  
  await dialog.locator('input[name="reference"]').fill(reference);
  await dialog.locator('input[name="description"]').fill(description);

  // 5️⃣ Fill Lines
  // Default lines are 2 empty lines
  const lines = overrides.lines ?? [
    { account: 'Cash in Banks', debit: '1000', credit: '0' },
    { account: 'Sales Revenue', debit: '0', credit: '1000' }, 
  ];

  // We need to ensure we have enough rows
  const addLineBtn = dialog.getByRole('button', { name: /Add Line/i });
  
  for (let i = 0; i < lines.length; i++) {
    // If row doesn't exist, add it
    const rows = dialog.locator('table tbody tr');
    const count = await rows.count();
    if (i >= count) {
        await addLineBtn.click();
    }
    
    const line = lines[i];
    const rowIndex = i;

    // Account (AsyncSelect)
    const row = rows.nth(rowIndex);
    const accountTrigger = row.locator('button[role="combobox"]');
    await accountTrigger.click();

    const searchInput = page.getByPlaceholder('Search...');
    if (await searchInput.isVisible()) {
        await searchInput.fill(line.account);
        await page.waitForTimeout(500); // Wait for debounce
    }
    
    // Select the option
    // Use partial match (exact: false) because options often contain codes (e.g. "11-1000 - Cash")
    const option = page.getByRole('option', { name: line.account });
    try {
        await expect(option.first()).toBeVisible({ timeout: 2000 });
        await option.first().click();
    } catch (e) {
        console.log(`Match not found for ${line.account}, selecting first option.`);
        const firstOption = page.getByRole('option').first();
        await expect(firstOption).toBeVisible();
        await firstOption.click();
    }
    // Wait for the option to disappear (listbox closes)
    await expect(page.getByRole('option').first()).not.toBeVisible();

    // Debit
    await row.locator(`input[name="lines.${rowIndex}.debit"]`).fill(line.debit);

    // Credit
    await row.locator(`input[name="lines.${rowIndex}.credit"]`).fill(line.credit);

    // Memo
    if (line.memo) {
        await row.locator(`input[name="lines.${rowIndex}.memo"]`).fill(line.memo);
    }
  }

  // 6️⃣ Submit
  const saveBtn = dialog.locator('button[type="submit"]');
  await expect(saveBtn).toBeEnabled(); 
  await saveBtn.click();

  // Wait for dialog to close
  await expect(dialog).not.toBeVisible({ timeout: 15000 });
  
  // Wait for table refresh
  await page.waitForResponse(r => r.url().includes('/api/journal-entries') && r.status() === 200).catch(() => null);

  return reference;
}

/**
 * Search for a journal entry by reference.
 */
export async function searchJournalEntry(page: Page, query: string): Promise<void> {
  const searchInput = page.getByPlaceholder(/Search/i);
  await expect(searchInput).toBeVisible({ timeout: 10000 });
  await searchInput.clear();
  await searchInput.fill(query);
  await searchInput.press('Enter');
  
  // Wait for API response
  await page.waitForResponse(r => r.url().includes('/api/journal-entries') && r.status() === 200).catch(() => null);
}

/**
 * Edit a journal entry.
 */
export async function editJournalEntry(
    page: Page,
    query: string,
    updates: { description?: string }
): Promise<void> {
    await searchJournalEntry(page, query);

    const row = page.locator('tr', { hasText: query }).first();
    await expect(row).toBeVisible();

    // Check status - must be draft to edit
    // Status is in the 7th column (index 6) roughly, but depends on column order.
    // Better to check for presence of Edit button.
    const editBtn = row.locator('button:has(svg.lucide-pencil)'); // Pencil icon
    if (!await editBtn.isVisible()) {
        throw new Error(`Edit button not found for ${query}. Status might not be 'draft'.`);
    }
    await editBtn.click();

    const dialog = page.getByRole('dialog', { name: /Edit/i }).first();
    await expect(dialog).toBeVisible();

    if (updates.description) {
        await dialog.locator('input[name="description"]').fill(updates.description);
    }

    const saveBtn = dialog.locator('button[type="submit"]');
    await expect(saveBtn).toBeVisible();
    
    await saveBtn.click();

    await expect(dialog).not.toBeVisible({ timeout: 15000 });
}

/**
 * View a journal entry.
 */
export async function viewJournalEntry(page: Page, query: string): Promise<void> {
    await searchJournalEntry(page, query);

    const row = page.locator('tr', { hasText: query }).first();
    await expect(row).toBeVisible();

    const viewBtn = row.locator('button:has(svg.lucide-eye)'); // Eye icon
    await expect(viewBtn).toBeVisible();
    await viewBtn.click();
}

/**
 * Delete a journal entry.
 */
export async function deleteJournalEntry(page: Page, query: string): Promise<void> {
    await searchJournalEntry(page, query);

    const row = page.locator('tr', { hasText: query }).first();
    await expect(row).toBeVisible();

    const deleteBtn = row.locator('button:has(svg.lucide-trash)'); // Trash icon
    
    // Check if delete button exists (only for draft)
    if (!await deleteBtn.isVisible()) {
         throw new Error(`Delete button not found for ${query}. Status might not be 'draft'.`);
    }
    
    await deleteBtn.click();

    // Confirm deletion
    const confirmBtn = page.getByRole('button', { name: /Delete|Confirm|Yes/i });
    await expect(confirmBtn).toBeVisible();
    await confirmBtn.click();

    // Wait for row to disappear
    await expect(row).not.toBeVisible();
}
