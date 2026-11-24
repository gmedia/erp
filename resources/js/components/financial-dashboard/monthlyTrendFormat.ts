/**
 * Pure formatting helpers for the monthly trend chart (V8-16 / V8-17).
 * Kept free of React so the sign and scale rules can be unit-tested.
 */

/** Compact amount with an explicit minus for negatives (e.g. -20.6M, 1.3K). */
export function formatCompactSigned(value: number): string {
    const absValue = Math.abs(value);
    const sign = value < 0 ? '-' : '';
    if (absValue === 0) return '0';
    if (absValue < 1000) return `${sign}${absValue.toString()}`;
    if (absValue < 1000000) return `${sign}${(absValue / 1000).toFixed(1)}K`;
    return `${sign}${(absValue / 1000000).toFixed(1)}M`;
}

/**
 * Signed bar height in px. Negative values keep their sign so they can be
 * drawn below the zero baseline; magnitude scales against the shared maxAbs.
 */
export function barHeightPx(
    value: number,
    maxAbs: number,
    maxPx = 160,
): number {
    if (value === 0 || maxAbs <= 0) return 0;
    return (Math.abs(value) / maxAbs) * maxPx;
}

/** Shared scale across revenue and expenses, counting negatives by magnitude. */
export function trendMaxAbs(
    rows: ReadonlyArray<{ revenue: number; expenses: number }>,
): number {
    return Math.max(
        1,
        ...rows.map((d) => Math.max(Math.abs(d.revenue), Math.abs(d.expenses))),
    );
}
