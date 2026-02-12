import { Page, expect } from '@playwright/test';
import { login } from '../helpers';

async function fillVisibleSearch(page: Page, value: string): Promise<void> {
  const input = page.locator('input[placeholder="Search..."]:visible');
  await expect(input).toHaveCount(1, { timeout: 15000 });
  await input.fill(value);
}

async function clickFirstMatchingOption(page: Page, name: RegExp): Promise<void> {
  for (let attempt = 0; attempt < 5; attempt++) {
    const option = page
      .locator('[role="option"]:visible')
      .filter({ hasText: name })
      .first();
    await option.waitFor({ state: 'visible', timeout: 15000 });
    try {
      await option.scrollIntoViewIfNeeded();
      await option.click();
      return;
    } catch (error) {
      if (attempt === 4) throw error;
    }
  }

  throw new Error(`Option not found: ${name}`);
}

export async function createAccountMapping(page: Page): Promise<{
  sourceCode: string;
  targetCode: string;
  notes: string;
}> {
  const timestamp = Date.now();

  await login(page);
  await page.goto('/account-mappings', { waitUntil: 'domcontentloaded' });

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
  // Use a regex that matches the option text more loosely to avoid issues with formatting
  await clickFirstMatchingOption(page, /COA 2025 Standard/);

  // 2. Select Source Account
  await combos.nth(1).click();
  
  // Helper to select first available option and return its code
  const selectFirstOptionAndGetCode = async (retries = 3): Promise<string> => {
      for (let i = 0; i < retries; i++) {
          try {
              // Wait for listbox to finish loading
              await expect(page.locator('[role="listbox"][aria-busy="false"]')).toBeVisible({ timeout: 10000 });
              
              const options = page.locator('[role="option"]');
              await expect(options.first()).toBeVisible({ timeout: 5000 });
              
              const firstOption = options.first();
              const text = await firstOption.textContent();
              if (!text) throw new Error('Option text missing');
              
              const code = text.split(' - ')[0].trim();
              await firstOption.click();
              return code;
          } catch (e) {
              if (i === retries - 1) throw e;
              await page.waitForTimeout(500);
          }
      }
      return ''; // Should not reach here
  };

  const sourceCodeDerived = await selectFirstOptionAndGetCode();


  // 3. Select Target COA Version
  await combos.nth(2).click();
  await fillVisibleSearch(page, 'COA 2026 Enhanced');
  await clickFirstMatchingOption(page, /COA 2026 Enhanced/);

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
    await skeleton.first().waitFor({ state: 'visible', timeout: 2000 });
    await skeleton.first().waitFor({ state: 'detached', timeout: 60000 });
  })();
  await input.press('Enter');
  await Promise.any([requestPromise, skeletonCyclePromise]).catch(() => {});
  await skeleton.first().waitFor({ state: 'detached', timeout: 60000 }).catch(() => {});
}

export function findAccountMappingRow(page: Page, sourceCode: string, targetCode: string) {
  return page.locator('tr', { hasText: sourceCode }).filter({ hasText: targetCode }).first();
}
