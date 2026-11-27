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

    test('journal update appears as Diubah with a localized summary and history', async ({
        page,
    }) => {
        await login(page, undefined, undefined, { requireDashboard: false });

        const token = await page.evaluate(() =>
            localStorage.getItem('api_token'),
        );
        expect(token).toBeTruthy();

        const api = page.context().request;
        const headers = {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
        };

        const findAccountId = async (search: string): Promise<number> => {
            const response = await api.get(
                `/api/accounts?search=${encodeURIComponent(search)}&per_page=100`,
                { headers },
            );
            expect(response.ok()).toBeTruthy();
            const payload = (await response.json()) as
                | Array<{ id: number; name: string }>
                | { data?: Array<{ id: number; name: string }> };
            const rows = Array.isArray(payload)
                ? payload
                : (payload.data ?? []);
            const match =
                rows.find(
                    (account) =>
                        account.name.toLowerCase() === search.toLowerCase(),
                ) ?? rows[0];
            expect(match).toBeTruthy();

            return match.id;
        };

        const debitAccountId = await findAccountId('Cash in Banks');
        const creditAccountId = await findAccountId('Sales Revenue');
        expect(debitAccountId).not.toBe(creditAccountId);

        const stamp = Date.now();
        const oldDescription = `E2E Audit Lama ${stamp}`;
        const newDescription = `E2E Audit Baru ${stamp}`;
        const entryDate = new Date().toISOString().slice(0, 10);
        const lines = [
            { account_id: debitAccountId, debit: 1000, credit: 0 },
            { account_id: creditAccountId, debit: 0, credit: 1000 },
        ];

        const createResponse = await api.post('/api/journal-entries', {
            headers,
            data: {
                entry_date: entryDate,
                description: oldDescription,
                lines,
            },
        });
        expect(createResponse.status(), await createResponse.text()).toBe(201);
        const created = (await createResponse.json()) as {
            data?: { id?: number };
        };
        const journalId = created.data?.id;
        expect(journalId).toBeTruthy();

        const updateResponse = await api.put(
            `/api/journal-entries/${journalId}`,
            {
                headers,
                data: {
                    entry_date: entryDate,
                    description: newDescription,
                    lines,
                },
            },
        );
        expect(updateResponse.ok(), await updateResponse.text()).toBeTruthy();

        await page.addInitScript(() =>
            localStorage.setItem('app_locale', 'id'),
        );
        await page.goto('/audit-logs');
        await expect(page.locator('table')).toBeVisible();
        await expect(page.locator('tbody tr').first()).toBeVisible();

        await page.getByRole('button', { name: 'Filter' }).click();
        const filterDialog = page.getByRole('dialog', { name: 'Filter' });
        await filterDialog
            .getByPlaceholder('e.g. JournalEntry')
            .fill('JournalEntry');
        await filterDialog
            .getByRole('button', { name: 'Terapkan filter' })
            .click();

        const auditRow = page
            .locator('tbody tr')
            .filter({
                has: page.getByRole('cell', {
                    name: String(journalId),
                    exact: true,
                }),
            })
            .filter({ hasText: 'Diubah' })
            .first();
        await expect(auditRow).toBeVisible();
        await expect(auditRow).toContainText('Mengubah');
        await expect(auditRow).toContainText('deskripsi');

        await page.goto('/journal-entries');
        const searchInput = page.getByPlaceholder(/Cari jurnal/i).first();
        await expect(searchInput).toBeVisible();
        await searchInput.fill(newDescription);
        await searchInput.press('Enter');

        const journalRow = page
            .locator('tbody tr')
            .filter({ hasText: newDescription })
            .first();
        await expect(journalRow).toBeVisible();
        await journalRow
            .getByRole('button', { name: 'Aksi' })
            .first()
            .click();
        // Row action labels are hardcoded English in ActionsDropdown.
        await page.getByRole('menuitem', { name: 'View' }).click();

        const viewDialog = page.getByRole('dialog', {
            name: 'Journal Entry Details',
        });
        await expect(viewDialog).toBeVisible();
        await expect(viewDialog.getByText('Riwayat perubahan')).toBeVisible();
        await expect(
            viewDialog.getByText('Diubah').first(),
        ).toBeVisible({ timeout: 15000 });
        await expect(viewDialog.getByText(oldDescription).first()).toBeVisible();
        await expect(viewDialog.getByText(newDescription).first()).toBeVisible();
    });
});
