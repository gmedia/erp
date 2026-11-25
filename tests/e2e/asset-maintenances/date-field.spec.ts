import { expect, test } from '@playwright/test';
import { expectResponseOk } from '../helpers';

import { changeDateFieldValue, openModule } from '../helpers';
import { createAssetMaintenance, searchAssetMaintenance } from './helpers';

test.describe('Asset Maintenance date field', () => {
    test.beforeEach(async ({ page }) => {
        await openModule(
            page,
            '/asset-maintenances',
            '/api/asset-maintenances',
        );
    });

    test('can change the scheduled date', async ({ page }) => {
        const identifier = await createAssetMaintenance(page, {
            notes: `MAINT-DATE-${Date.now()}`,
        });
        await searchAssetMaintenance(page, identifier);

        const row = page
            .locator('tbody tr')
            .filter({ hasText: identifier })
            .first();
        await expect(row).toBeVisible();

        await row.getByRole('button', { name: /Actions/i }).click();
        await page.getByRole('menuitem', { name: /Edit/i }).click();

        const dialog = page.getByRole('dialog', {
            name: /Edit Asset Maintenance/i,
        });
        await expect(dialog).toBeVisible();

        const scheduledDate = await changeDateFieldValue(
            page,
            dialog,
            'Scheduled At',
        );

        const updateRequestPromise = page.waitForRequest(
            (request) =>
                request.url().includes('/api/asset-maintenances/') &&
                ['PUT', 'PATCH'].includes(request.method()),
        );
        const updateResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/asset-maintenances/') &&
                ['PUT', 'PATCH'].includes(response.request().method()),
        );

        await dialog
            .getByRole('button', { name: /Update Maintenance|Update/i })
            .last()
            .click();

        const updateRequest = await updateRequestPromise;
        const payload = updateRequest.postDataJSON() as {
            scheduled_at?: string;
        };

        expect(payload.scheduled_at).toBe(`${scheduledDate} 00:00:00`);

        await expectResponseOk(await updateResponsePromise);
        await expect(dialog).not.toBeVisible();
    });
});
