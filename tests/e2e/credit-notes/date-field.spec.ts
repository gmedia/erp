import { expect, test } from '@playwright/test';

import { changeDateFieldValue, openModule } from '../helpers';
import { createCreditNote, searchCreditNote } from './helpers';

test.describe('Credit Note date field', () => {
    test.beforeEach(async ({ page }) => {
        await openModule(page, '/credit-notes', '/api/credit-notes');
    });

    test('can change the credit note date', async ({ page }) => {
        const creditNoteNumber = await createCreditNote(page);
        await searchCreditNote(page, creditNoteNumber);

        const row = page
            .locator('tbody tr')
            .filter({ hasText: creditNoteNumber })
            .first();
        await expect(row).toBeVisible();

        await row.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: 'Edit' }).click();

        const dialog = page.getByRole('dialog', {
            name: /Edit Credit Note/i,
        });
        await expect(dialog).toBeVisible();

        const creditNoteDate = await changeDateFieldValue(
            page,
            dialog,
            'Credit Note Date',
        );

        const updateRequestPromise = page.waitForRequest(
            (request) =>
                request.url().includes('/api/credit-notes/') &&
                ['PUT', 'PATCH'].includes(request.method()),
        );
        const updateResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/credit-notes/') &&
                ['PUT', 'PATCH'].includes(response.request().method()) &&
                response.status() < 400,
        );

        await dialog.getByRole('button', { name: /Update/i }).click();

        const updateRequest = await updateRequestPromise;
        const payload = updateRequest.postDataJSON() as {
            credit_note_date?: string;
        };

        expect(payload.credit_note_date).toBe(creditNoteDate);

        await updateResponsePromise;
        await expect(dialog).not.toBeVisible();
    });
});
