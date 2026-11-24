import { readdirSync, readFileSync } from 'node:fs';
import { dirname, join, relative, resolve } from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

/**
 * Guard formatter (W7-09): format uang dan tanggal di kolom tabel wajib lewat
 * helper regional (formatCurrencyByRegionalSettings, formatDateByRegionalSettings,
 * formatDateTimeByRegionalSettings), bukan format manual yang mengabaikan
 * pengaturan perusahaan (Intl.NumberFormat, toLocaleDateString, toFixed, ...).
 *
 * Cakupan: berkas kolom tabel (*Columns.tsx) di resources/js.
 */

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const scanDir = resolve(root, 'resources/js');

// Pengecualian yang disengaja (relatif ke root repo) + alasannya.
const allowlist = new Map([
    // Contoh bentuk allowlist; isi hanya bila ada alasan kuat.
]);

const patterns = [
    {
        name: 'Intl.NumberFormat (gunakan formatCurrencyByRegionalSettings / formatNumberByRegionalSettings)',
        regex: /new\s+Intl\.NumberFormat\s*\(/,
    },
    {
        name: 'toLocaleDateString/toLocaleTimeString/toLocaleString (gunakan formatDateByRegionalSettings)',
        regex: /\.toLocale(?:Date|Time)?String\s*\(/,
    },
    {
        name: 'toFixed (gunakan formatNumberByRegionalSettings)',
        regex: /\.toFixed\s*\(/,
    },
];

function walk(dir) {
    return readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
        const path = join(dir, entry.name);
        if (entry.isDirectory()) {
            return walk(path);
        }
        return /Columns\.tsx?$/.test(entry.name) ? [path] : [];
    });
}

const violations = [];

for (const file of walk(scanDir)) {
    const rel = relative(root, file).split('\\').join('/');
    if (allowlist.has(rel)) {
        continue;
    }

    readFileSync(file, 'utf8')
        .split('\n')
        .forEach((line, index) => {
            for (const pattern of patterns) {
                if (pattern.regex.test(line)) {
                    violations.push(
                        `${rel}:${index + 1}  ${line.trim()}\n      -> ${pattern.name}`,
                    );
                }
            }
        });
}

if (violations.length > 0) {
    console.error(
        `Found ${violations.length} manual money/date format(s) in table columns:\n`,
    );
    console.error(violations.map((v) => `  ${v}`).join('\n'));
    console.error(
        '\nUse regional helpers from resources/js/utils/number-format.ts and utils/date-format.ts.',
    );
    process.exit(1);
}

console.log(
    `Formatter check passed: 0 manual money/date formats in *Columns.tsx (${allowlist.size} allowlisted file).`,
);
