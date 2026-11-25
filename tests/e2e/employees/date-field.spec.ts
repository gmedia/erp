import { expect, test } from '@playwright/test';

import { changeDateFieldValue, expectResponseOk, openModule } from '../helpers';
import { createEmployee, searchEmployee } from './helpers';

test.describe('Employee date field', () => {
    test.beforeEach(async ({ page }) => {
        await openModule(page, '/employees', '/api/employees');
    });

    test('can change the hire date', async ({ page }) => {
        const email = await createEmployee(page);
        await searchEmployee(page, email);

        const row = page.locator('tbody tr').filter({ hasText: email }).first();
        await expect(row).toBeVisible();

        await row.getByRole('button', { name: /Actions/i }).click();
        await page.getByRole('menuitem', { name: /Edit/i }).click();

        const dialog = page.getByRole('dialog', { name: /Edit Employee/i });
        await expect(dialog).toBeVisible();

        const expectedDate = await changeDateFieldValue(
            page,
            dialog,
            'Hire Date',
        );

        const updateRequestPromise = page.waitForRequest(
            (request) =>
                request.url().includes('/api/employees/') &&
                ['PUT', 'PATCH'].includes(request.method()),
        );
        const updateResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/employees/') &&
                ['PUT', 'PATCH'].includes(response.request().method()),
        );

        await dialog.getByRole('button', { name: /Update/i }).click();

        const updateRequest = await updateRequestPromise;
        const payload = updateRequest.postDataJSON() as {
            current_employment?: { hire_date?: string };
        };

        expect(payload.current_employment?.hire_date).toBe(expectedDate);

        await expectResponseOk(await updateResponsePromise);
        await expect(dialog).not.toBeVisible();
    });
});
