import { expect, test } from '@playwright/test';

test.use({ viewport: { width: 390, height: 844 }, colorScheme: 'dark' });

test('TabsList profil aset di mobile 390 tetap satu baris dan bisa di-scroll horizontal', async ({
    page,
}) => {
    await page.goto('/assets');
    const adminToken = await page.evaluate(() =>
        localStorage.getItem('api_token'),
    );
    expect(adminToken, 'api_token harus ada di localStorage').toBeTruthy();

    const listResponse = await page.request.get(
        '/api/assets?search=FA-000007',
        {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${adminToken}`,
            },
        },
    );
    expect(listResponse.status()).toBeLessThan(400);
    const list = (await listResponse.json()) as {
        data: { ulid: string; asset_code: string }[];
    };
    const asset = list.data.find((a) => a.asset_code === 'FA-000007');
    expect(asset, 'aset FA-000007 hasil seed harus ada').toBeTruthy();

    const profileResponse = page.waitForResponse(
        (response) =>
            response.url().includes(`/api/assets/${asset!.ulid}/profile`) &&
            response.request().method() === 'GET',
    );
    await page.goto(`/assets/${asset!.ulid}`);
    expect((await profileResponse).status()).toBeLessThan(400);

    const tabsList = page.locator('[data-slot="tabs-list"]').first();
    await expect(tabsList).toBeVisible();

    const { height, lineHeight } = await tabsList.evaluate((el) => {
        const style = getComputedStyle(el);
        return {
            height: el.getBoundingClientRect().height,
            lineHeight: parseFloat(style.lineHeight) || 36,
        };
    });
    expect(height).toBeLessThanOrEqual(lineHeight * 1.5 + 16);

    const overflowX = await tabsList.evaluate(
        (el) => getComputedStyle(el).overflowX,
    );
    expect(overflowX).toBe('auto');

    await expect
        .poll(() =>
            tabsList.evaluate((el) => el.getAttribute('data-scroll-end')),
        )
        .toBe('false');
    await expect
        .poll(() =>
            tabsList.evaluate((el) => getComputedStyle(el).maskImage),
        )
        .toContain('linear-gradient');

    await tabsList.evaluate((el) => {
        el.scrollLeft = el.scrollWidth;
        el.dispatchEvent(new Event('scroll'));
    });
    await expect
        .poll(() =>
            tabsList.evaluate((el) => el.getAttribute('data-scroll-end')),
        )
        .toBe('true');
    await expect
        .poll(() =>
            tabsList.evaluate((el) => getComputedStyle(el).maskImage),
        )
        .toBe('none');

    const tabStyles = await tabsList.evaluate((el) => {
        const active = el.querySelector('[data-state="active"]');
        const inactive = el.querySelector('[data-state="inactive"]');
        if (!active || !inactive) {
            return null;
        }
        return {
            activeBg: getComputedStyle(active).backgroundColor,
            inactiveBg: getComputedStyle(inactive).backgroundColor,
        };
    });
    expect(tabStyles).not.toBeNull();
    expect(tabStyles!.activeBg).not.toBe(tabStyles!.inactiveBg);
});
