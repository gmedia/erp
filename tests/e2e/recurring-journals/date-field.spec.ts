import { expect, test } from '@playwright/test';

import {
    changeDateFieldValue,
    expectResponseOk,
    openModule,
    reloadAndWaitForApi,
    searchAndWaitForApi,
} from '../helpers';

test.describe('Recurring Journal date field', () => {
    test.beforeEach(async ({ page }) => {
        await openModule(
            page,
            '/recurring-journals',
            '/api/recurring-journals',
        );
    });

    test('can change the next run date', async ({ page }) => {
        const journalName = await page.evaluate(async () => {
            const token = localStorage.getItem('api_token') || '';
            const headers = {
                'X-Requested-With': 'XMLHttpRequest',
                Authorization: `Bearer ${token}`,
            };

            const accountsResponse = await fetch('/api/accounts?per_page=2', {
                headers,
            });
            const accountsPayload = await accountsResponse.json();
            const accounts = accountsPayload.data || accountsPayload;

            const response = await fetch('/api/recurring-journals', {
                method: 'POST',
                headers: {
                    ...headers,
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    name: `E2E Recurring ${Date.now()}`,
                    frequency: 'monthly',
                    next_run_date: new Date().toISOString().slice(0, 10),
                    auto_post: false,
                    is_active: true,
                    description_template: 'E2E recurring journal',
                    lines: [
                        { account_id: accounts[0].id, debit: 1000, credit: 0 },
                        { account_id: accounts[1].id, debit: 0, credit: 1000 },
                    ],
                }),
            });

            if (!response.ok) {
                throw new Error(
                    `Failed to create recurring journal: ${response.status} ${await response.text()}`,
                );
            }

            const payload = await response.json();

            return payload.data.name as string;
        });

        await reloadAndWaitForApi(page, '/api/recurring-journals');

        const searchInput = page.getByPlaceholder(/Search name, description/i);
        await searchAndWaitForApi(
            page,
            searchInput,
            journalName,
            '/api/recurring-journals',
        );

        const row = page
            .locator('tbody tr')
            .filter({ hasText: journalName })
            .first();
        await expect(row).toBeVisible();

        await row.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: 'Edit' }).click();

        const dialog = page.getByRole('dialog', {
            name: /Edit Recurring Journal/i,
        });
        await expect(dialog).toBeVisible();

        const expectedDate = await changeDateFieldValue(
            page,
            dialog,
            'Next Run Date',
        );

        const updateRequestPromise = page.waitForRequest(
            (request) =>
                request.url().includes('/api/recurring-journals/') &&
                ['PUT', 'PATCH'].includes(request.method()),
        );
        const updateResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/recurring-journals/') &&
                ['PUT', 'PATCH'].includes(response.request().method()),
        );

        await dialog.getByRole('button', { name: /Update/i }).click();

        const updateRequest = await updateRequestPromise;
        const payload = updateRequest.postDataJSON() as {
            next_run_date?: string;
        };

        expect(payload.next_run_date).toBe(expectedDate);

        await expectResponseOk(await updateResponsePromise);
        await expect(dialog).not.toBeVisible();
    });
});
