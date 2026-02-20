import { Page, expect } from '@playwright/test';

export async function createAssetStocktake(page: Page) {
    // Open dialog
    await page.getByRole('button', { name: /add|buat/i }).click();
    await expect(page.getByRole('dialog')).toBeVisible();

    const timestamp = Date.now();
    const reference = `ST-${timestamp}`;

    // 1. Branch (Async Select) - assuming it's the first combobox or labeled "Branch"
    // Shadcn select trigger often doesn't associate with label correctly for getByLabel without ID.
    // But let's try locating by nearby label text.
    await page.locator('label:has-text("Branch")').locator('..').getByRole('combobox').click();
    
    // Wait for options and select first
    const option = page.getByRole('option').first();
    await expect(option).toBeVisible();
    await option.click();

    // 2. Reference
    await page.getByLabel('Reference').fill(reference);

    // 3. Planned Date
    // Click date picker trigger
    await page.locator('label:has-text("Planned Date")').locator('..').getByRole('button').click();
    // Select today or a date
    await page.getByRole('gridcell').first().click(); // Select first available day in calendar

    // 4. Status (Select) - defaults to draft, but let's verify or set
    // It's already draft by default in form.

    // Save
    await page.getByRole('dialog').locator('button[type="submit"]').click();

    // Wait for dialog to close
    await expect(page.getByRole('dialog')).not.toBeVisible();

    return reference;
}

export async function searchAssetStocktake(page: Page, reference: string) {
    const searchInput = page.getByPlaceholder(/search/i);
    const responsePromise = page
        .waitForResponse(
            resp => resp.url().includes('/api/asset-stocktakes') && resp.status() < 400,
            { timeout: 5000 },
        )
        .catch(() => null);
    await searchInput.fill(reference);
    await responsePromise;
}
