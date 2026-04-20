import { Page, expect } from '@playwright/test';

export async function createAssetStocktake(page: Page) {
    const formDialog = page.getByRole('dialog', {
        name: /add new asset stocktake|edit asset stocktake/i,
    });

    // Open dialog
    await page.getByRole('button', { name: /add|buat/i }).click();
    await expect(formDialog).toBeVisible();

    const timestamp = Date.now();
    const reference = `ST-${timestamp}`;

    // 1. Branch (Async Select) - assuming it's the first combobox or labeled "Branch"
    // Shadcn select trigger often doesn't associate with label correctly for getByLabel without ID.
    // But let's try locating by nearby label text.
    await formDialog
        .locator('label:has-text("Branch")')
        .locator('..')
        .getByRole('combobox')
        .click();
    
    // Wait for options and select first
    const option = page.locator('[role="option"]:visible, ul[aria-busy]:visible button:visible').first();
    await expect(option).toBeVisible();
    await option.click({ force: true });

    // 2. Reference
    await page.getByLabel('Reference').fill(reference);

    // 3. Planned Date
    // Click date picker trigger
    await formDialog
        .locator('label:has-text("Planned Date")')
        .locator('..')
        .getByRole('button')
        .click();
    // Select today or a date
    await page.getByRole('gridcell').first().click(); // Select first available day in calendar

    // 4. Status (Select) - defaults to draft, but let's verify or set
    // It's already draft by default in form.

    // Save
    await formDialog.locator('button[type="submit"]').click();

    // Wait for dialog to close
    await expect(formDialog).not.toBeVisible();

    return reference;
}

export async function searchAssetStocktake(page: Page, reference: string) {
    const searchInput = page.getByPlaceholder(/search/i);
    await expect(searchInput).toBeVisible();
    const normalizedReference = reference.trim();
    if ((await searchInput.inputValue()).trim() === normalizedReference) {
        return;
    }

    const responsePromise = page
        .waitForResponse(
            resp => resp.url().includes('/api/asset-stocktakes') && resp.status() < 400,
            { timeout: 5000 },
        )
        ;
    await searchInput.clear();
    await searchInput.fill(normalizedReference);
    await searchInput.press('Enter');
    await responsePromise;
}

export async function editAssetStocktake(
    page: Page,
    reference: string,
    updates: Record<string, string>,
) {
    await searchAssetStocktake(page, reference);

    const row = page.locator('tbody tr').filter({ hasText: reference }).first();
    await expect(row).toBeVisible();

    await row.getByRole('button', { name: /Actions/i }).click();
    await page.getByRole('menuitem', { name: /Edit/i }).click();

    const formDialog = page.getByRole('dialog', {
        name: /edit asset stocktake/i,
    });
    await expect(formDialog).toBeVisible();

    if (updates.reference) {
        await formDialog.getByLabel('Reference').fill(updates.reference);
    }

    const updateResponsePromise = page.waitForResponse(
        (response) =>
            response.url().includes('/api/asset-stocktakes') &&
            ['PUT', 'PATCH'].includes(response.request().method()) &&
            response.status() < 400,
        { timeout: 15000 },
    );

    await formDialog.locator('button[type="submit"]').click();
    await updateResponsePromise;
    await expect(formDialog).not.toBeVisible({ timeout: 15000 });
}

export async function navigateToPerformStocktake(page: Page, reference: string) {
    await searchAssetStocktake(page, reference);

    // Find the row containing the reference
    const row = page.getByRole('row', { name: new RegExp(reference, 'i') }).first();
    
    // Click the actions dropdown menu
    await row.getByRole('button', { name: /open menu|actions/i }).click();
    
    // Click 'Perform' action
    await page.getByRole('menuitem', { name: /perform/i }).click();

    // Verify navigation to perform page
    await expect(page).toHaveURL(/\/asset-stocktakes\/[0-9A-HJKMNP-TV-Z]{26}\/perform/i);
}
