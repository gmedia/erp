import { Page, expect } from '@playwright/test';

/**
 * Pick a day from an open DatePicker calendar (line-row placeholders).
 */
async function pickCalendarDay(page: Page, triggerText: RegExp, day: string): Promise<void> {
  const dialog = page.getByRole('dialog').last();
  const trigger = dialog.getByRole('button', { name: triggerText }).first();
  await expect(trigger).toBeVisible();
  await trigger.click();

  const calendar = page.locator('[data-slot="calendar"]').last();
  await calendar.waitFor({ state: 'visible', timeout: 15000 });

  const dayButton = calendar
    .locator('button')
    .filter({ hasText: new RegExp(`^${day}$`) })
    .first();
  await dayButton.waitFor({ state: 'visible' });
  await dayButton.click({ force: true });

  await page.keyboard.press('Escape');
  await expect(calendar).not.toBeVisible().catch(() => null);
}

/**
 * Select the first visible option from an AsyncSelect / combobox.
 */
async function selectFirstComboboxOption(
  page: Page,
  trigger: ReturnType<Page['locator']>,
): Promise<void> {
  await expect(trigger).toBeVisible();
  await trigger.click();

  const option = page
    .locator('[role="option"]:visible, ul[aria-busy]:visible button:visible')
    .first();
  await expect(option).toBeVisible({ timeout: 15000 });
  await option.click({ force: true });
  await expect(
    page.locator('[role="option"]:visible, ul[aria-busy]:visible button:visible'),
  )
    .toHaveCount(0, { timeout: 10000 })
    .catch(() => null);
}

/**
 * Create a new budget via the UI (requires ≥1 budget line).
 */
export async function createBudget(
  page: Page,
  overrides: Partial<{ name: string; budget_type: string }> = {},
): Promise<string> {
  const timestamp = Date.now();
  const name = overrides.name ?? `Budget ${timestamp}`;

  const addButton = page.getByRole('button', { name: /Add/i });
  await expect(addButton).toBeVisible();
  await addButton.click();

  const dialog = page.getByRole('dialog', { name: /Add New Budget/i });
  await expect(dialog).toBeVisible();

  await dialog.locator('input[name="name"]').fill(name);

  // Budget type (defaults to Operational; override when provided)
  if (overrides.budget_type) {
    const typeTrigger = dialog.getByRole('combobox', { name: /Budget Type/i });
    await typeTrigger.click();
    await page
      .getByRole('option', { name: overrides.budget_type, exact: true })
      .click();
  }

  // Fiscal year — preferred meta may auto-select; otherwise pick first option
  const fyTrigger = dialog.locator('button').filter({ hasText: /Select fiscal year/i });
  if (await fyTrigger.isVisible().catch(() => false)) {
    await selectFirstComboboxOption(page, fyTrigger);
  } else {
    // Already auto-selected — wait briefly for preferred resolution
    await page.waitForTimeout(500);
  }

  // Add one budget line (inline editing row)
  const addLineBtn = dialog.getByRole('button', { name: /Add Line/i });
  await expect(addLineBtn).toBeVisible();
  await addLineBtn.click();

  // Account (AsyncSelect)
  const accountTrigger = dialog
    .locator('button')
    .filter({ hasText: /Select account/i })
    .first();
  await selectFirstComboboxOption(page, accountTrigger);

  // Period dates
  await pickCalendarDay(page, /Pick start date/i, '1');
  await pickCalendarDay(page, /Pick end date/i, '28');

  // Allocated amount
  await dialog.locator('input[name="lines.0.allocated_amount"]').fill('1000000');

  // Commit line edit mode (green pencil) so row is not stuck open — optional
  const commitLineBtn = dialog.locator('button:has(svg.lucide-pencil)').first();
  if (await commitLineBtn.isVisible().catch(() => false)) {
    await commitLineBtn.click();
  }

  const responsePromise = page.waitForResponse(
    (r) =>
      r.url().includes('/api/budgets') &&
      r.request().method() === 'POST' &&
      r.status() < 400,
    { timeout: 20000 },
  );

  const submitButton = dialog.getByRole('button', { name: /Add/i }).last();
  await expect(submitButton).toBeVisible();
  await submitButton.click();
  await responsePromise;
  await expect(dialog).not.toBeVisible({ timeout: 15000 });

  return name;
}

/**
 * Search budgets by name.
 */
export async function searchBudget(page: Page, name: string): Promise<void> {
  const searchInput = page.getByPlaceholder('Search budgets...');
  await searchInput.waitFor({ state: 'visible' });
  const normalized = name.trim();
  if ((await searchInput.inputValue()).trim() === normalized) {
    return;
  }

  const responsePromise = page.waitForResponse(
    (r) => r.url().includes('/api/budgets') && r.status() < 400,
  );
  await searchInput.clear();
  await searchInput.fill(normalized);
  await searchInput.press('Enter');
  await responsePromise;
}

/**
 * Edit an existing budget (name only — keeps lines intact).
 */
export async function editBudget(
  page: Page,
  identifier: string,
  updates: Record<string, string>,
): Promise<void> {
  const row = page.locator('tbody tr').filter({ hasText: identifier }).first();
  await expect(row).toBeVisible();
  await row.getByRole('button').last().click();
  await page.getByRole('menuitem', { name: 'Edit' }).click();

  const dialog = page.getByRole('dialog', { name: /Edit Budget/i });
  await expect(dialog).toBeVisible();

  if (updates.name) {
    await dialog.locator('input[name="name"]').fill(updates.name);
  }

  const responsePromise = page.waitForResponse(
    (r) =>
      r.url().includes('/api/budgets') &&
      ['PUT', 'PATCH', 'POST'].includes(r.request().method()) &&
      r.status() < 400,
    { timeout: 20000 },
  );

  const updateBtn = dialog.getByRole('button', { name: /Update/i });
  await expect(updateBtn).toBeVisible();
  await updateBtn.click();
  await responsePromise;
  await expect(dialog).not.toBeVisible({ timeout: 15000 });
}
