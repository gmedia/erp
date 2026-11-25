import { expect, test } from '@playwright/test';

test.use({
    viewport: { width: 390, height: 844 },
    colorScheme: 'dark',
});

test('TabsList profil aset di 390 gelap: petunjuk geser hilang di ujung scroll dan tab aktif berbeda', async ({
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

    await page.goto(`/assets/${asset!.ulid}`);

    const tabsList = page.locator('[data-slot="tabs-list"]').first();
    await expect(tabsList).toBeVisible();

    const scrollHint = () =>
        tabsList.evaluate((el) => ({
            scrollEnd: el.getAttribute('data-scroll-end'),
            mask:
                getComputedStyle(el).maskImage ||
                getComputedStyle(el).webkitMaskImage,
            scrollable: el.scrollWidth > el.clientWidth,
        }));

    await expect.poll(async () => (await scrollHint()).scrollEnd).toBe('false');
    expect((await scrollHint()).scrollable).toBe(true);
    await expect.poll(async () => (await scrollHint()).mask).not.toBe('none');

    await tabsList.evaluate((el) => {
        el.scrollLeft = el.scrollWidth;
        el.dispatchEvent(new Event('scroll'));
    });

    await expect.poll(async () => (await scrollHint()).scrollEnd).toBe('true');
    expect((await scrollHint()).mask).toBe('none');

    const tabColors = await tabsList.evaluate((el) => {
        const active = el.querySelector('[data-state="active"]');
        const inactive = el.querySelector('[data-state="inactive"]');
        if (!active || !inactive) {
            return null;
        }
        return {
            activeBg: getComputedStyle(active).backgroundColor,
            activeFg: getComputedStyle(active).color,
            inactiveFg: getComputedStyle(inactive).color,
        };
    });
    expect(tabColors).not.toBeNull();
    expect(tabColors!.activeBg).not.toBe('rgba(0, 0, 0, 0)');
    expect(tabColors!.activeFg).not.toBe(tabColors!.inactiveFg);
});
