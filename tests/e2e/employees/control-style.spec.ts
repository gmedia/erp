import { expect, test } from '@playwright/test';

import { openModule } from '../helpers';

type ControlStyle = {
    height: string;
    borderTopColor: string;
    borderRadius: string;
    paddingLeft: string;
    color: string;
    chevronRight: number;
};

async function measureControls(
    page: import('@playwright/test').Page,
): Promise<Record<string, ControlStyle | null>> {
    return page.evaluate(() => {
        const pick = (el: Element | null): ControlStyle | null => {
            if (!el) {
                return null;
            }
            const cs = getComputedStyle(el);
            const svg = el.querySelector('svg');
            const rect = el.getBoundingClientRect();
            const svgRect = svg?.getBoundingClientRect();
            return {
                height: cs.height,
                borderTopColor: cs.borderTopColor,
                borderRadius: cs.borderRadius,
                paddingLeft: cs.paddingLeft,
                color: cs.color,
                chevronRight: svgRect
                    ? Math.round(rect.right - svgRect.right)
                    : -1,
            };
        };

        const dialog = document.querySelector('[role="dialog"]');
        const input = dialog?.querySelector('input:not([type="hidden"])');
        const selectTrigger = dialog?.querySelector(
            '[data-slot="select-trigger"]',
        );
        const combobox = dialog?.querySelector('[role="combobox"]');
        const datePicker = dialog?.querySelector(
            'button[data-state], button.justify-start, button.text-left',
        );

        return {
            input: pick(input ?? null),
            select: pick(selectTrigger ?? null),
            asyncSelect: pick(combobox ?? null),
            datePicker: pick(datePicker ?? null),
        };
    });
}

test.describe('Employees control style (issue #584)', () => {
    test.beforeEach(async ({ page }) => {
        await page.addInitScript(() =>
            localStorage.setItem('app_locale', 'id'),
        );
        await openModule(page, '/employees', '/api/employees');
    });

    test('form controls share height, inset and chevron position', async ({
        page,
    }) => {
        await page.getByRole('button', { name: /Tambah|Add/i }).first().click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();

        const styles = await measureControls(page);
        console.log('CONTROL_STYLES ' + JSON.stringify(styles));
        await dialog.screenshot({
            path: '/tmp/opencode/584-employees-dialog.png',
        });

        for (const key of ['input', 'select', 'asyncSelect', 'datePicker'] as const) {
            expect(styles[key], `${key} missing`).not.toBeNull();
            expect(styles[key]?.height).toBe('32px');
            expect(styles[key]?.borderTopColor).toMatch(
                /^(rgba\(0, 0, 0, 0\)|transparent)$/,
            );
            expect(styles[key]?.paddingLeft).toBe('10px');
        }

        for (const key of ['select', 'asyncSelect', 'datePicker'] as const) {
            expect(styles[key]?.chevronRight).toBeGreaterThanOrEqual(8);
            expect(styles[key]?.chevronRight).toBeLessThanOrEqual(10);
        }
    });
});
