import { expect, test } from '@playwright/test';
import { expectResponseOk, login } from '../helpers';
import { generateModuleTests } from '../shared-test-factories';
import { createPosition, editPosition, searchPosition } from './helpers';

test.describe('Position default sort indicator', () => {
    test('header Created At menampilkan indikator sort aktif turun by default', async ({
        page,
    }) => {
        await login(page, undefined, undefined, { requireDashboard: false });

        const responsePromise = page.waitForResponse(
            (r) => r.url().includes('/api/positions'),
            { timeout: 30000 },
        );
        await page.goto('/positions');
        await expectResponseOk(await responsePromise);

        const createdAtSort = page
            .locator('thead')
            .getByRole('button', { name: 'Created At', exact: true });
        await expect(createdAtSort).toBeVisible();
        await expect(createdAtSort).toHaveAttribute('data-sort-state', 'desc');
    });
});

generateModuleTests({
    entityName: 'Position',
    entityNamePlural: 'Positions',
    route: '/positions',
    apiPath: '/api/positions',

    // Callbacks
    createEntity: (page) => createPosition(page),
    searchEntity: (page, identifier) => searchPosition(page, identifier),
    editEntity: (page, identifier, updates) =>
        editPosition(page, identifier, updates),
    editUpdates: { name: `Pos-Updated-${Date.now()}` },

    // DataTable
    sortableColumns: ['Name', 'Created At', 'Updated At'],

    // View
    viewType: 'dialog',
    viewDialogTitle: 'Position Details',

    // Export
    exportApiPath: '/api/positions/export',
    expectedExportColumns: ['ID', 'Name', 'Created At', 'Updated At'],
});
