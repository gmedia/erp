import { expect, test } from '@playwright/test';
import { login } from '../helpers';
import { VEHICLE_LOAN_REQUEST_PATH } from './helpers';

test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => localStorage.setItem('app_locale', 'id'));
});

test('request form breadcrumb ends with Permintaan Peminjaman Kendaraan', async ({
    page,
}) => {
    await login(page);
    await page.goto(VEHICLE_LOAN_REQUEST_PATH);

    const breadcrumb = page.locator('nav[data-slot="breadcrumb"]');
    await expect(breadcrumb).toContainText('Peminjaman Kendaraan');
    await expect(
        breadcrumb.locator('[data-slot="breadcrumb-page"]'),
    ).toHaveText('Permintaan Peminjaman Kendaraan');
});

test('schedule date buttons are wide enough to show the placeholder', async ({
    page,
}) => {
    await login(page);
    await page.goto(VEHICLE_LOAN_REQUEST_PATH);

    for (const id of ['start_at_date', 'end_at_date']) {
        const trigger = page.locator(`#${id}`);
        await expect(trigger).toBeVisible();
        const fits = await trigger.evaluate(
            (el) => el.scrollWidth <= el.clientWidth,
        );
        expect(fits, `${id} text must not be clipped`).toBeTruthy();
        const width = await trigger.evaluate(
            (el) => el.getBoundingClientRect().width,
        );
        expect(width).toBeGreaterThanOrEqual(140);
    }
});

test('assignment select placeholder stays on one line', async ({ page }) => {
    await login(page);
    await page.goto(VEHICLE_LOAN_REQUEST_PATH);

    for (const id of ['driver_id', 'vehicle_unit_id']) {
        const trigger = page.locator(`#${id}`);
        await expect(trigger).toBeVisible();
        const height = await trigger.evaluate(
            (el) => el.getBoundingClientRect().height,
        );
        expect(height).toBeLessThanOrEqual(40);

        const valueLines = await trigger
            .locator('[data-slot="select-value"]')
            .evaluate((el) => {
                const lineHeight =
                    parseFloat(getComputedStyle(el).lineHeight) || 20;
                return Math.round(
                    el.getBoundingClientRect().height / lineHeight,
                );
            });
        expect(valueLines).toBeLessThanOrEqual(1);
    }
});
