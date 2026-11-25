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
    });
});
