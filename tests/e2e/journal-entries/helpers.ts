import { randomUUID } from 'node:crypto';
import { Page, expect } from '@playwright/test';

/**
 * Create a new journal entry via the UI.
 */
export async function createJournalEntry(
  page: Page,
  overrides: Partial<{
    entry_date: string;
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
  const random = randomUUID().slice(0, 8);
  const defaultRef = `REF-${timestamp}-${random}`;
  const defaultDesc = `Journal Entry ${timestamp}`;

  const addButton = page.getByRole('button', { name: /Add/i });
  await expect(addButton).toBeVisible();
  await addButton.click();

  const dialog = page.getByRole('dialog', { name: /Journal Entry/i }).first();
  await expect(dialog).toBeVisible();

  const reference = overrides.reference ?? defaultRef;
  const description = overrides.description ?? defaultDesc;

  if (overrides.entry_date) {
    const dateBtn = dialog.locator('button').filter({ hasText: /Pick a date/i }).first();
    if (await dateBtn.isVisible()) {
        await dateBtn.click();
    }
  }
  
  await dialog.locator('input[name="reference"]').fill(reference);
  await dialog.locator('input[name="description"]').fill(description);

  const lines = overrides.lines ?? [
    { account: 'Cash in Banks', debit: '1000', credit: '0' },
    { account: 'Sales Revenue', debit: '0', credit: '1000' }, 
  ];

  const addLineBtn = dialog.getByRole('button', { name: /Add Line/i });
  
  for (let i = 0; i < lines.length; i++) {
    await addLineBtn.click();
    const line = lines[i];

    const lineDialog = page.getByRole('dialog', { name: /Add Line/i }).first();
    await expect(lineDialog).toBeVisible();

    const accountTrigger = lineDialog.locator('button[role="combobox"]');
    await accountTrigger.click();

    const searchInput = page.getByPlaceholder('Search...');
    if (await searchInput.isVisible()) {
        await searchInput.fill(line.account);
        await page.waitForTimeout(500); 
    }
    
    const option = page
      .locator('[role="option"]:visible, ul[aria-busy]:visible button:visible')
      .filter({ hasText: new RegExp(line.account, 'i') });
    try {
        await expect(option.first()).toBeVisible({ timeout: 2000 });
      await option.first().click({ force: true });
    } catch {
      const firstOption = page.locator('[role="option"]:visible, ul[aria-busy]:visible button:visible').first();
      await expect(firstOption).toBeVisible();
      await firstOption.click({ force: true });
    }
    await expect(page.locator('[role="option"]:visible, ul[aria-busy]:visible button:visible')).toHaveCount(0, { timeout: 10000 }).catch(() => null);
    
    await lineDialog.locator('input[name="debit"]').fill(line.debit);
    await lineDialog.locator('input[name="credit"]').fill(line.credit);

    if (line.memo) {
        await lineDialog.locator('input[name="memo"]').fill(line.memo);
    }
    const saveLineBtn = lineDialog.locator('button[type="submit"]');
    await saveLineBtn.click();
    await expect(lineDialog).not.toBeVisible({ timeout: 5000 });
  }

  const saveBtn = dialog.locator('form').first().locator('button[type="submit"]');
  await expect(saveBtn).toBeEnabled(); 
  await saveBtn.click();

  await expect(dialog).not.toBeVisible({ timeout: 15000 });
  await page.waitForResponse(r => r.url().includes('/api/journal-entries') && r.status() === 200).catch(() => null);                                            
  return reference;
}

export async function searchJournalEntry(page: Page, query: string): Promise<void> {                                                                              
  const searchInput = page.getByPlaceholder(/Search/i);
  await expect(searchInput).toBeVisible({ timeout: 10000 });
  await searchInput.clear();
  await searchInput.fill(query);
  await searchInput.press('Enter');
  
  await page.waitForResponse(r => r.url().includes('/api/journal-entries') && r.status() === 200).catch(() => null);                                            
}

export async function editJournalEntry(
    page: Page,
    query: string,
    updates: { description?: string }
): Promise<void> {
    await searchJournalEntry(page, query);

    const row = page.locator('tr', { hasText: query }).first();
    await expect(row).toBeVisible();

    const editBtn = row.locator('button:has(svg.lucide-pencil)'); 
    if (!await editBtn.isVisible()) {
        throw new Error(`Edit button not found for ${query}. Status might not be 'draft'.`);                                                                        
    }
    await editBtn.click();

    const dialog = page.getByRole('dialog', { name: /Edit/i }).first();
    await expect(dialog).toBeVisible();

    if (updates.description) {
        await dialog.locator('input[name="description"]').fill(updates.description);                                                                                
    }

    const saveBtn = dialog.locator('form').first().locator('button[type="submit"]');
    await expect(saveBtn).toBeVisible();
    await saveBtn.click();
    await expect(dialog).not.toBeVisible({ timeout: 15000 });
}

export async function viewJournalEntry(page: Page, query: string): Promise<void> {                                                                                  
    await searchJournalEntry(page, query);
    const row = page.locator('tr', { hasText: query }).first();
    await expect(row).toBeVisible();
    const viewBtn = row.locator('button:has(svg.lucide-eye)'); 
    await expect(viewBtn).toBeVisible();
    await viewBtn.click();
}

export async function deleteJournalEntry(page: Page, query: string): Promise<void> {                                                                                
    await searchJournalEntry(page, query);

  const deleteBtn = page
    .locator('tr', { hasText: query })
    .first()
    .locator('button:has(svg.lucide-trash)');
  await expect(deleteBtn).toBeVisible();
  await deleteBtn.click();

  const confirmDialog = page.getByRole('alertdialog').first();
  if (await confirmDialog.isVisible().catch(() => false)) {
    const confirmBtn = confirmDialog.getByRole('button', {
      name: /Delete|Confirm|Yes/i,
    });
    await expect(confirmBtn.first()).toBeVisible();
    await confirmBtn.first().click();
  }

  await page
    .waitForResponse(
      (r) =>
        r.url().includes('/api/journal-entries') &&
        [200, 204].includes(r.status()),
      { timeout: 10000 },
    )
    .catch(() => null);
}
