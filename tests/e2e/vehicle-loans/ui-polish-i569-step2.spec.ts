import { expect, test } from '@playwright/test';
import { login } from '../helpers';
import {
    seedApprovedLoan,
    VEHICLE_LOAN_DASHBOARD_PATH,
    VEHICLE_LOAN_PICKUP_PATH,
    VEHICLE_LOAN_REQUEST_PATH,
    VEHICLE_LOAN_SECURITY_PATH,
} from './helpers';

test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => localStorage.setItem('app_locale', 'id'));
});

test('detail page shows Diajukan once and a clear back link', async ({
    page,
}) => {
    await login(page);
    await page.goto(VEHICLE_LOAN_REQUEST_PATH);
    const loanId = await seedApprovedLoan(page);

    await page.goto(`/vehicle-loans/${loanId}`);
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();

    await expect(page.locator('dt', { hasText: /^Diajukan$/ })).toHaveCount(1);
    await expect(
        page.getByRole('link', { name: 'Kembali ke daftar' }),
    ).toBeVisible();
});

test('detail page keeps the activity timeline in the right column', async ({
    page,
}) => {
    await login(page);
    await page.goto(VEHICLE_LOAN_REQUEST_PATH);
    const loanId = await seedApprovedLoan(page);

    await page.goto(`/vehicle-loans/${loanId}`);
    const activityTitle = page.getByText('Riwayat Aktivitas', { exact: true });
    const approvalTitle = page.getByText('Persetujuan', { exact: true });
    await expect(activityTitle).toBeVisible();

    const activityBox = await activityTitle.boundingBox();
    const approvalBox = await approvalTitle.boundingBox();
    expect(activityBox).not.toBeNull();
    expect(approvalBox).not.toBeNull();
    // Right column: activity sits at the same x as the approval card, and
    // below it, not beside the left column content.
    expect(
        Math.abs((activityBox?.x ?? 0) - (approvalBox?.x ?? 0)),
    ).toBeLessThan(4);
});

test('dashboard latest list aligns the date column across rows', async ({
    page,
}) => {
    // Desktop (sm+) is the layout where the date is a fixed column; below sm
    // the rows stack on purpose, so the alignment rule applies to sm+ only.
    await page.setViewportSize({ width: 1440, height: 900 });
    await login(page);
    await page.goto(VEHICLE_LOAN_REQUEST_PATH);
    await seedApprovedLoan(page);
    await seedApprovedLoan(page);

    await page.goto(VEHICLE_LOAN_DASHBOARD_PATH);
    await expect(
        page.getByRole('heading', { name: 'Permintaan Terbaru' }),
    ).toBeVisible();

    const section = page
        .locator('section')
        .filter({ hasText: 'Permintaan Terbaru' });
    const xs = await section
        .locator('p.tabular-nums')
        .evaluateAll((nodes) =>
            nodes.map((node) => Math.round(node.getBoundingClientRect().left)),
        );
    expect(xs.length).toBeGreaterThan(0);
    expect(new Set(xs).size, `date column x positions: ${xs.join(',')}`).toBe(
        1,
    );
});

test('no mismatched "maks." vs "maksimal" wording on photo hints', async ({
    page,
}) => {
    await login(page);
    await page.goto(VEHICLE_LOAN_REQUEST_PATH);
    const body = (await page.locator('body').textContent()) ?? '';
    expect(body).not.toContain('maks. 5 MB');
});

test('sidebar submenu uses Permintaan, Dasbor and Antrean Keamanan', async ({
    page,
}) => {
    await login(page);
    await page.goto(VEHICLE_LOAN_REQUEST_PATH);

    const sidebar = page.locator('[data-slot="sidebar-content"]').first();
    await expect(
        sidebar.getByText('Permintaan', { exact: true }),
    ).toBeVisible();
    await expect(
        sidebar.locator(`a[href="${VEHICLE_LOAN_DASHBOARD_PATH}"]`),
    ).toBeVisible();
    await expect(
        sidebar.getByText('Antrean Keamanan', { exact: true }),
    ).toBeVisible();
});

test('request form breadcrumb leaf matches the page title', async ({
    page,
}) => {
    await login(page);
    await page.goto(VEHICLE_LOAN_REQUEST_PATH);

    const breadcrumb = page.locator('nav[data-slot="breadcrumb"]');
    await expect(
        breadcrumb.locator('[data-slot="breadcrumb-page"]'),
    ).toHaveText('Permintaan Peminjaman Kendaraan');
});

test('disabled submit button on pickup has no vertical artifact', async ({
    page,
}) => {
    await login(page);
    await page.goto(VEHICLE_LOAN_PICKUP_PATH);
    const submit = page.getByRole('button', { name: 'Kirim Pengambilan' });
    await expect(submit).toBeDisabled();

    const style = await submit.evaluate((node) => {
        const cs = getComputedStyle(node);
        const childBorders = Array.from(node.querySelectorAll('*')).filter(
            (el) => parseFloat(getComputedStyle(el).borderLeftWidth) > 0,
        ).length;
        return {
            boxShadow: cs.boxShadow,
            borderLeft: cs.borderLeftWidth,
            borderRight: cs.borderRightWidth,
            childBorders,
        };
    });
    expect(style.boxShadow).toBe('none');
    expect(style.borderLeft).toBe('0px');
    expect(style.borderRight).toBe('0px');
    expect(style.childBorders).toBe(0);
});

test('form pages keep a narrow max width and list pages a wide one', async ({
    page,
}) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await login(page);

    // Form page: max-w-2xl (672px).
    await page.goto(VEHICLE_LOAN_REQUEST_PATH);
    const form = await page
        .locator('div.mx-auto.max-w-2xl')
        .first()
        .boundingBox();
    expect(form?.width ?? 0).toBeLessThanOrEqual(672);

    // Full-width list page: max-w-7xl (1280px).
    await page.goto(VEHICLE_LOAN_SECURITY_PATH);
    const list = await page
        .locator('div.mx-auto.max-w-7xl')
        .first()
        .boundingBox();
    expect(list?.width ?? 0).toBeLessThanOrEqual(1280);
    expect(list?.width ?? 0).toBeGreaterThan(672);
});

test('security queue cards in the same grid row share a top edge', async ({
    page,
}) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await login(page);
    await seedApprovedLoan(page);
    await seedApprovedLoan(page);
    await seedApprovedLoan(page);
    await page.goto(VEHICLE_LOAN_SECURITY_PATH);

    const queueGrid = page
        .locator('[data-slot="card"]')
        .first()
        .locator('xpath=..');
    const cards = queueGrid.locator(':scope > [data-slot="card"]');
    await expect(cards.first()).toBeVisible({ timeout: 15_000 });
    await expect(cards.nth(2)).toBeVisible();

    const tops: number[] = [];
    for (let i = 0; i < 3; i++) {
        const box = await cards.nth(i).boundingBox();
        tops.push(Math.round(box?.y ?? -1));
    }

    expect(tops[1]).toBe(tops[0]);
    expect(tops[2]).toBe(tops[0]);
});
