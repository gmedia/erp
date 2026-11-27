import { expect, test } from '@playwright/test';
import { login } from '../helpers';

test.describe('Audit Log', () => {
    test('audit log page lists recorded changes and filters by module', async ({
        page,
    }) => {
        await login(page, undefined, undefined, { requireDashboard: false });

        await page.goto('/audit-logs');
        await expect(page.locator('table')).toBeVisible();
        await expect(page.getByText('Loading...')).not.toBeVisible();

        const rows = page.locator('tbody tr');
        await expect(rows.first()).toBeVisible();

        await page.getByRole('button', { name: 'Filters' }).click();
        const filterDialog = page.getByRole('dialog', { name: 'Filters' });
        await filterDialog
            .getByPlaceholder('e.g. JournalEntry')
            .fill('JournalEntry');
        await filterDialog
            .getByRole('button', { name: 'Apply Filters' })
            .click();

        await expect
            .poll(async () => {
                const texts = await rows.allTextContents();
                return (
                    texts.length > 0 &&
                    texts.every((text) => text.includes('JournalEntry'))
                );
            })
            .toBe(true);
    });
});
