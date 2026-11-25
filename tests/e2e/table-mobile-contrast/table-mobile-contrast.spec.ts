import { expect, test } from '@playwright/test';
import { login } from '../helpers';

function luminance(rgb: [number, number, number]): number {
    const [r, g, b] = rgb.map((c) => {
        const v = c / 255;
        return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4;
    });

    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

function contrastRatio(
    a: [number, number, number],
    b: [number, number, number],
): number {
    const la = luminance(a);
    const lb = luminance(b);

    return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
}

function parseRgb(value: string): [number, number, number] {
    const match = value.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
    if (!match) {
        throw new Error(`Tidak bisa membaca warna: ${value}`);
    }

    return [Number(match[1]), Number(match[2]), Number(match[3])];
}

test.describe('Issue #460: checkbox mode terang', () => {
    test.use({ viewport: { width: 1440, height: 900 }, colorScheme: 'light' });

    test('border checkbox kontras >= 3:1 terhadap latar', async ({ page }) => {
        await login(page);
        await page.goto('/budgets');
        await page.waitForLoadState('networkidle');

        const checkbox = page.locator('[data-slot="checkbox"]:visible').first();
        await expect(checkbox).toBeVisible();

        const colors = await checkbox.evaluate((el) => {
            const toRgb = (value: string): string => {
                const canvas = document.createElement('canvas');
                canvas.width = 1;
                canvas.height = 1;
                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    return value;
                }
                ctx.fillStyle = value;
                ctx.fillRect(0, 0, 1, 1);
                const [r, g, b] = ctx.getImageData(0, 0, 1, 1).data;

                return `rgb(${r}, ${g}, ${b})`;
            };

            const bg = (node: Element | null): string => {
                let current: Element | null = node;
                while (current) {
                    const value = getComputedStyle(current).backgroundColor;
                    if (!/rgba\(0,\s*0,\s*0,\s*0\)|transparent/.test(value)) {
                        return value;
                    }
                    current = current.parentElement;
                }
                return 'rgb(255, 255, 255)';
            };

            return {
                border: toRgb(getComputedStyle(el).borderTopColor),
                bg: toRgb(bg(el.parentElement)),
            };
        });

        const ratio = contrastRatio(
            parseRgb(colors.border),
            parseRgb(colors.bg),
        );
        expect(ratio).toBeGreaterThanOrEqual(3);
    });
});

test.describe('Issue #460: petunjuk scroll kolom aksi di 390', () => {
    test.use({ viewport: { width: 390, height: 844 }, colorScheme: 'light' });

    test('shadow kolom aksi ada di awal dan hilang di ujung scroll', async ({
        page,
    }) => {
        await login(page);
        await page.goto('/budgets');
        await page.waitForLoadState('networkidle');

        const actionsHead = page
            .locator('th[data-sticky-column="actions"]')
            .first();
        await expect(actionsHead).toBeVisible();

        const shadowOf = () =>
            actionsHead.evaluate(
                (el) => getComputedStyle(el, '::before').backgroundImage,
            );

        const scrollerOf = () =>
            actionsHead.evaluate((el) => {
                const scroller = el.closest('.overflow-x-auto');
                return scroller ? scroller.scrollLeft : -1;
            });

        expect(await scrollerOf()).toBe(0);
        expect(await shadowOf()).not.toBe('none');

        await actionsHead.evaluate((el) => {
            const scroller = el.closest('.overflow-x-auto');
            if (scroller) scroller.scrollLeft = scroller.scrollWidth;
        });

        await expect.poll(shadowOf).toBe('none');
    });
});

test.describe('Issue #460: kontras mode gelap dan sidebar', () => {
    test.use({ viewport: { width: 1440, height: 900 }, colorScheme: 'dark' });

    test('badge netral memiliki kontras teks >= 4.5:1 di mode gelap', async ({
        page,
    }) => {
        await login(page);
        await page.goto('/employees');
        await page.waitForLoadState('networkidle');

        const badge = page.locator('[data-slot="badge"]:visible').first();
        await expect(badge).toBeVisible();

        const styles = await badge.evaluate((el) => {
            const toRgb = (value: string): string => {
                const canvas = document.createElement('canvas');
                canvas.width = 1;
                canvas.height = 1;
                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    return value;
                }
                ctx.fillStyle = value;
                ctx.fillRect(0, 0, 1, 1);
                const [r, g, b] = ctx.getImageData(0, 0, 1, 1).data;

                return `rgb(${r}, ${g}, ${b})`;
            };

            const cs = getComputedStyle(el);
            let bg = cs.backgroundColor;
            let node: HTMLElement | null = el as HTMLElement;
            while (
                node &&
                /rgba\(0,\s*0,\s*0,\s*0\)|transparent/.test(bg)
            ) {
                node = node.parentElement;
                bg = node ? getComputedStyle(node).backgroundColor : bg;
            }

            return { fg: toRgb(cs.color), bg: toRgb(bg) };
        });

        const ratio = contrastRatio(
            parseRgb(styles.fg),
            parseRgb(styles.bg),
        );
        expect(ratio).toBeGreaterThanOrEqual(4.5);
    });

    test('item sidebar pertama dan terakhir terlihat penuh', async ({
        page,
    }) => {
        await login(page);
        await page.goto('/employees');
        await page.waitForLoadState('networkidle');

        const content = page.locator('[data-slot="sidebar-content"]');
        await expect(content).toBeVisible();

        const items = content.locator('[data-slot="sidebar-menu-button"]');
        const count = await items.count();
        expect(count).toBeGreaterThan(1);

        const containerBox = await content.boundingBox();
        expect(containerBox).not.toBeNull();

        for (const index of [0, count - 1]) {
            const box = await items.nth(index).boundingBox();
            expect(box).not.toBeNull();
            if (!box || !containerBox) {
                continue;
            }

            expect(box.y).toBeGreaterThanOrEqual(containerBox.y);
            expect(box.y + box.height).toBeLessThanOrEqual(
                containerBox.y + containerBox.height,
            );
        }

        const footer = page.locator('[data-slot="sidebar-footer"]');
        const footerBox = await footer.boundingBox();
        const lastBox = await items.nth(count - 1).boundingBox();
        expect(footerBox).not.toBeNull();
        expect(lastBox).not.toBeNull();
        if (footerBox && lastBox) {
            expect(lastBox.y + lastBox.height).toBeLessThanOrEqual(
                footerBox.y + 1,
            );
        }
    });

});
