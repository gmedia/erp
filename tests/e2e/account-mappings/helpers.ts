import { Page, expect } from '@playwright/test';

async function fillVisibleSearch(page: Page, value: string): Promise<void> {
  const input = page.locator('input[placeholder="Search..."]:visible');
  await expect(input).toHaveCount(1, { timeout: 15000 });
  await input.fill(value);
}

async function clickFirstMatchingOption(page: Page, name: RegExp): Promise<void> {
  const options = page.locator('[role="option"]:visible').filter({ hasText: name });
  await expect(options.first()).toBeVisible({ timeout: 15000 });
  
  const option = options.first();
  await option.scrollIntoViewIfNeeded();
  await option.click();
  
  // Wait for the listbox/dropdown to disappear after selection
  await expect(page.locator('[role="listbox"]:visible')).toHaveCount(0, { timeout: 15000 });
}


/**
 * Create a new account mapping via the UI.
 * NOTE: Assumes page is already logged in and on /account-mappings (handled by test.beforeEach).
 */
export async function createAccountMapping(page: Page): Promise<{
  sourceCode: string;
  targetCode: string;
  notes: string;
}> {
  const timestamp = Date.now();

  const addBtn = page.getByRole('button', { name: /Add/i }).first();
  await expect(addBtn).toBeVisible({ timeout: 15000 });
  await addBtn.click();

  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible({ timeout: 15000 });

  const combos = dialog.locator('[role="combobox"]');
  await expect(combos.first()).toBeVisible({ timeout: 15000 });

  // 1. Select Source COA Version
  await combos.nth(0).click();
  await fillVisibleSearch(page, 'COA 2025 Standard');
  // Use a more specific regex to avoid issues with formatting
  await clickFirstMatchingOption(page, /COA 2025 Standard \(/);

  // 2. Select Source Account
  await combos.nth(1).click();
  
  // Helper to select first available option and return its code
  const selectFirstOptionAndGetCode = async (retries = 3): Promise<string> => {
      // Small wait to ensure dropdown animations or data loading has started
      await page.waitForTimeout(500);
      
      for (let i = 0; i < retries; i++) {
          try {
              // Wait for listbox to finish loading
              const listbox = page.locator('[role="listbox"]:visible');
              await expect(listbox).toBeVisible({ timeout: 10000 });
              await expect(listbox).not.toHaveAttribute('aria-busy', 'true', { timeout: 10000 });
              
              const options = listbox.locator('[role="option"]');
              await expect(options.first()).toBeVisible({ timeout: 5000 });
              
              const firstOption = options.first();
              const text = await firstOption.textContent();
              if (!text) throw new Error('Option text missing');
              
              const code = text.split(' - ')[0].trim();
              await firstOption.click();
              
              // Wait for this specific listbox to disappear
              await expect(listbox).not.toBeVisible({ timeout: 10000 });
              
              return code;
          } catch (e) {
              if (i === retries - 1) throw e;
              await page.waitForTimeout(1000);
          }
      }
      return ''; // Should not reach here
  };

  const sourceCodeDerived = await selectFirstOptionAndGetCode();


  // 3. Select Target COA Version
  await combos.nth(2).click();
  await fillVisibleSearch(page, 'COA 2026 Enhanced');
  await clickFirstMatchingOption(page, /COA 2026 Enhanced \(/);

  // 4. Select Target Account
  await combos.nth(3).click();
  const targetCodeDerived = await selectFirstOptionAndGetCode();

  // 5. Select Type (Rename)
  await combos.nth(4).click();
  await clickFirstMatchingOption(page, /^Rename$/);


  const notes = `notes-${timestamp}`;
  await dialog.locator('textarea[name="notes"]').fill(notes);

  const submitBtn = dialog.getByRole('button', { name: /Create|Submit/i });
  await expect(submitBtn).toBeVisible();
  await submitBtn.click();

  await expect(dialog).not.toBeVisible({ timeout: 15000 });
  
  return { sourceCode: sourceCodeDerived || '52000', targetCode: targetCodeDerived || '11120', notes };
}

export async function searchAccountMappings(page: Page, query: string): Promise<void> {
  const input = page.getByPlaceholder('Search account mappings...');
  await input.waitFor({ state: 'visible' });
  const skeleton = page.locator('tbody .h-4.w-full.bg-muted');
  await input.fill(query);
  const requestPromise = page.waitForResponse(
    (response) =>
      response.url().includes('/api/account-mappings') &&
      response.request().method() === 'GET' &&
      response.status() < 400,
    { timeout: 60000 },
  );
  const skeletonCyclePromise = (async () => {
    await skeleton.first().waitFor({ state: 'visible', timeout: 10000 });
    await skeleton.first().waitFor({ state: 'detached', timeout: 60000 });
  })();
  await input.press('Enter');
  await Promise.any([requestPromise, skeletonCyclePromise]).catch(() => {});
  await skeleton.first().waitFor({ state: 'detached', timeout: 60000 }).catch(() => {});
}

export function findAccountMappingRow(page: Page, sourceCode: string) {
  return page.locator('tr', { hasText: sourceCode }).first();
}

/**
 * Edit an existing account mapping.
 */
export async function editAccountMapping(
  page: Page,
  sourceCode: string,
  updates: { notes?: string; type?: string }
): Promise<void> {
  await searchAccountMappings(page, sourceCode);

  const row = findAccountMappingRow(page, sourceCode);
  await expect(row).toBeVisible();
  
  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await actionsBtn.click();

  const editItem = page.getByRole('menuitem', { name: /Edit/i });
  await editItem.click();

  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();

  if (updates.notes) {
    await dialog.locator('textarea[name="notes"]').fill(updates.notes);
  }

  if (updates.type) {
    const typeTrigger = dialog.locator('button').filter({ hasText: /Rename|Merge|Split/i });
    await typeTrigger.click();
    await page.getByRole('option', { name: updates.type, exact: true }).click();
  }

  const updateBtn = dialog.getByRole('button', { name: /Update/i });
  await updateBtn.click();

  await expect(dialog).not.toBeVisible({ timeout: 15000 });
}
