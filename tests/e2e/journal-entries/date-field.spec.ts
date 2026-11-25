import { expect, test } from '@playwright/test';

import { changeDateFieldValue, expectResponseOk, openModule } from '../helpers';
import { createJournalEntry, searchJournalEntry } from './helpers';

test.describe('Journal Entry date field', () => {
    test.beforeEach(async ({ page }) => {
        await openModule(page, '/journal-entries', '/api/journal-entries');
    });

    test('can change the entry date', async ({ page }) => {
        const reference = await createJournalEntry(page);
        await searchJournalEntry(page, reference);

        const row = page
            .locator('tbody tr')
            .filter({ hasText: reference })
            .first();
        await expect(row).toBeVisible();

        await row.getByRole('button', { name: 'Actions' }).first().click();
        await page.getByRole('menuitem', { name: 'Edit' }).click();

        const dialog = page.getByRole('dialog', { name: /Edit/i }).first();
        await expect(dialog).toBeVisible();

        const expectedDate = await changeDateFieldValue(page, dialog, 'Date');

        const updateRequestPromise = page.waitForRequest(
            (request) =>
                request.url().includes('/api/journal-entries/') &&
                ['PUT', 'PATCH'].includes(request.method()),
        );
        const updateResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/journal-entries/') &&
                ['PUT', 'PATCH'].includes(response.request().method()),
        );

        await dialog
            .locator('form')
            .first()
            .locator('button[type="submit"]')
            .click();

        const updateRequest = await updateRequestPromise;
        const payload = updateRequest.postDataJSON() as {
            entry_date?: string;
        };

        expect(payload.entry_date).toBe(expectedDate);

        await expectResponseOk(await updateResponsePromise);
        await expect(dialog).not.toBeVisible();
    });
});
