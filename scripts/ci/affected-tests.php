<?php

declare(strict_types=1);

/**
 * Affected-tests mapper for CI (see .omo/plans/affected-tests-ci.md).
 *
 * Given changed files, prints JSON:
 *   { full_pest, full_e2e, pest_groups[], e2e_dirs[], ignored[], reasons[] }
 *
 * Default is deny: anything under code roots that cannot be resolved forces FULL.
 *
 * Usage:
 *   php scripts/ci/affected-tests.php --files <file-with-paths>
 *   php scripts/ci/affected-tests.php --self-test
 *   (CI) git diff --name-only base...head | php scripts/ci/affected-tests.php
 */

$opts = getopt('', ['files:', 'self-test', 'repo:']);

$root = rtrim($opts['repo'] ?? getcwd(), '/');

// E2E dirs currently excluded from full CI (so selective mirrors full CI).
const E2E_EXCLUDED = [
    'aging-dashboard', 'ap-aging-report', 'ap-outstanding-report', 'ap-payment-history-report',
    'ar-aging-report', 'ar-outstanding-report', 'budget-variance-report', 'budgets',
    'customer-statement-report', 'financial-dashboard', 'fiscal-year-auto-select', 'misc',
    'per-branch-financial-reports', 'social-login', 'vehicle-loans', 'visual-audit',
];

const IGNORE_PATTERNS = [
    '#^(docs/|\.omo/)#',
    '#\.md$#',
    '#^public/#',
    '#^\.gitignore$#',
    '#^LICENSE#',
    '#^CHANGELOG#',
    '#\.(png|jpg|jpeg|svg|gif|webp|ico)$#',
];

const FULL_BOTH = [
    '#^(bootstrap/|config/|app/Providers/|app/Models/|app/Http/Middleware/)#',
    '#^composer\.(json|lock)$#',
    '#^package(-lock)?\.json$#',
    '#^\.github/workflows/#',
    '#^scripts/ci/#',
    '#^docker/#',
    '#^docker-compose.*\.ya?ml$#',
    '#^Dockerfile$#',
    '#^\.env\.example$#',
    '#^app/Actions/(Concerns|Reports|AccountingPosting|EntityStates|Approvals|ApprovalFlows|ApprovalDelegations|ApprovalMonitoring)/#',
    '#^app/(Http/Controllers|Exports|Domain)/Concerns/#',
    '#^app/(Services|Console|DTOs|Imports|Support|Traits|Policies|Jobs|Events|Listeners|Notifications)/#',
    '#^routes/(api|web|console)\.php$#',
];

const FULL_PEST = [
    '#^tests/(TestCase|Pest)\.php$#',
    '#^phpunit\.xml$#',
    '#^database/factories/#',
    '#^database/migrations/#',
    '#^app/Exports/#',
];

const FULL_E2E = [
    '#^database/seeders/#',
    '#^database/migrations/#',
    '#^tests/e2e/(helpers|shared-test-factories|global-setup)\.ts$#',
    '#^playwright\.config\.ts$#',
    '#^resources/js/(components/(common|ui)|lib|hooks|utils|layouts|contexts|types)/#',
    '#^resources/js/(app-routes|app|main)\.tsx$#',
];

const MODULE_PATTERNS = [
    '#^app/Actions/([A-Za-z0-9]+)/#',
    '#^app/Domain/([A-Za-z0-9]+)/#',
    '#^app/Http/Controllers/(?:[A-Za-z]+/)?([A-Za-z0-9]+)Controller\.php$#',
    '#^app/Http/Requests/([A-Za-z0-9]+)/#',
    '#^app/Http/Resources/([A-Za-z0-9]+?)(Resource|Collection)?\.php$#',
    '#^app/Exports/([A-Za-z0-9]+?)(Export|Collection|IndexExport)?\.php$#',
    '#^routes/api/([a-z0-9-]+)\.php$#',
    '#^resources/js/pages/([a-z0-9-]+)/#',
    '#^resources/js/components/([a-z0-9-]+)/#',
    '#^tests/Feature/([A-Za-z0-9]+)/#',
    '#^tests/e2e/([a-z0-9-]+)/#',
];

function matchesAny(string $path, array $patterns): bool
{
    foreach ($patterns as $re) {
        if (preg_match($re, $path)) {
            return true;
        }
    }

    return false;
}

function kebab(string $value): string
{
    $value = preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $value) ?? $value;
    $value = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1-$2', $value) ?? $value;

    return strtolower(str_replace('_', '-', $value));
}

function slugAlias(string $slug): string
{
    $aliases = ['dashboards' => 'dashboard'];

    return $aliases[$slug] ?? $slug;
}

function scanGroups(string $root): array
{
    $groups = [];
    $dir = $root.'/tests';
    if (!is_dir($dir)) {
        return [];
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        $content = file_get_contents($file->getPathname());
        if (preg_match_all("/->group\('([^']+)'\)/", (string) $content, $m)) {
            foreach ($m[1] as $group) {
                $groups[$group] = true;
            }
        }
    }

    return array_keys($groups);
}

function scanE2EDirs(string $root): array
{
    $dirs = [];
    foreach (glob($root.'/tests/e2e/*', GLOB_ONLYDIR) ?: [] as $dir) {
        $name = basename($dir);
        if (!in_array($name, E2E_EXCLUDED, true)) {
            $dirs[$name] = true;
        }
    }

    return array_keys($dirs);
}

function fileGroups(string $root, string $path): array
{
    $full = $root.'/'.$path;
    if (!is_file($full)) {
        return [];
    }
    $content = file_get_contents($full);
    if (!$content || !preg_match_all("/->group\('([^']+)'\)/", $content, $m)) {
        return [];
    }

    return array_values(array_unique($m[1]));
}

function candidates(string $slug): array
{
    $variants = [$slug, slugAlias($slug)];
    if (str_ends_with($slug, 'y')) {
        $variants[] = substr($slug, 0, -1).'ies';
    }
    $variants[] = $slug.'s';
    $variants[] = $slug.'es';

    return array_values(array_unique(array_filter($variants)));
}

/**
 * @return array{full_pest:bool,full_e2e:bool,pest_groups:array,e2e_dirs:array,ignored:array,reasons:array}
 */
function mapFiles(string $root, array $files, array $groups, array $e2eDirs): array
{
    $fullPest = false;
    $fullE2e = false;
    $pest = [];
    $e2e = [];
    $ignored = [];
    $reasons = [];

    foreach ($files as $path) {
        $path = ltrim(trim($path), './');
        if ($path === '') {
            continue;
        }

        if (matchesAny($path, IGNORE_PATTERNS)) {
            $ignored[] = $path;
            continue;
        }

        // Excluded E2E dirs are intentionally not run in CI.
        if (preg_match('#^tests/e2e/([^/]+)/#', $path, $ex) && in_array($ex[1], E2E_EXCLUDED, true)) {
            $ignored[] = $path;
            $reasons[] = "excluded_e2e: $path";
            continue;
        }

        // Deleted test file: cannot derive its group safely.
        if (preg_match('#^tests/(Feature|Unit)/#', $path) && !is_file($root.'/'.$path)) {
            $fullPest = true;
            $reasons[] = "deleted_test: $path";
            continue;
        }

        $both = matchesAny($path, FULL_BOTH);
        $isFullPest = $both || matchesAny($path, FULL_PEST);
        $isFullE2e = $both || matchesAny($path, FULL_E2E);
        if ($isFullPest) {
            $fullPest = true;
            $reasons[] = "full_pest: $path";
        }
        if ($isFullE2e) {
            $fullE2e = true;
            $reasons[] = "full_e2e: $path";
        }

        $resolved = $isFullPest || $isFullE2e;

        if (preg_match('#^tests/(Feature|Unit)/#', $path)) {
            // Changed test file: trust its own ->group() tags.
            foreach (fileGroups($root, $path) as $group) {
                if (in_array($group, $groups, true)) {
                    $pest[$group] = true;
                } else {
                    $fullPest = true;
                    $reasons[] = "unknown_group: $path -> $group";
                }
                $resolved = true;
            }
        } else {
            foreach (MODULE_PATTERNS as $re) {
                if (!preg_match($re, $path, $m)) {
                    continue;
                }
                foreach (candidates(kebab($m[1])) as $slug) {
                    if (in_array($slug, $groups, true)) {
                        $pest[$slug] = true;
                        $resolved = true;
                    }
                    if (in_array($slug, $e2eDirs, true)) {
                        $e2e[$slug] = true;
                        $resolved = true;
                    }
                }
                break;
            }
        }

        $isCodeRoot = (bool) preg_match('#^(app/|routes/|resources/js/|database/|tests/)#', $path);
        if (!$resolved && $isCodeRoot) {
            $fullPest = true;
            $fullE2e = true;
            $reasons[] = "unmapped: $path";
        }
    }

    $pestGroups = array_keys($pest);
    $e2eDirsSelected = array_keys($e2e);
    sort($pestGroups);
    sort($e2eDirsSelected);

    return [
        'full_pest' => $fullPest,
        'full_e2e' => $fullE2e,
        'pest_groups' => $pestGroups,
        'e2e_dirs' => $e2eDirsSelected,
        'ignored' => $ignored,
        'reasons' => array_values(array_unique($reasons)),
    ];
}

function runSelfTest(string $root): int
{
    $groups = scanGroups($root);
    $dirs = scanE2EDirs($root);
    $failures = 0;

    $cases = [
        ['docs/readme.md', ['full_pest' => false, 'full_e2e' => false]],
        ['app/Actions/Accounts/CreateAccount.php', ['pest_has' => 'accounts', 'e2e_has' => 'accounts']],
        ['app/Http/Controllers/SupplierBillController.php', ['pest_has' => 'supplier-bills', 'e2e_has' => 'supplier-bills']],
        ['app/Actions/Reports/SalesReportAction.php', ['full_pest' => true, 'full_e2e' => true]],
        ['app/Actions/AccountingPosting/PostJournal.php', ['full_pest' => true, 'full_e2e' => true]],
        ['app/Actions/Concerns/InteractsWithExportableQuery.php', ['full_pest' => true, 'full_e2e' => true]],
        ['app/Http/Controllers/Concerns/StoresItemsInTransaction.php', ['full_pest' => true, 'full_e2e' => true]],
        ['app/Models/Account.php', ['full_pest' => true, 'full_e2e' => true]],
        ['app/Services/FormulaEvaluatorService.php', ['full_pest' => true, 'full_e2e' => true]],
        ['database/migrations/2026_01_01_000000_x.php', ['full_pest' => true, 'full_e2e' => true]],
        ['database/seeders/DatabaseSeeder.php', ['full_pest' => false, 'full_e2e' => true]],
        ['resources/js/components/ui/button.tsx', ['full_pest' => false, 'full_e2e' => true]],
        ['resources/js/pages/dashboards/index.tsx', ['pest_has' => 'dashboard', 'e2e_has' => 'dashboards']],
        ['tests/e2e/social-login/x.spec.ts', ['full_e2e' => false]],
        ['app/Console/Commands/Foo.php', ['full_pest' => true, 'full_e2e' => true]],
    ];

    foreach ($cases as [$file, $expect]) {
        $result = mapFiles($root, [$file], $groups, $dirs);
        foreach ($expect as $key => $value) {
            $ok = match (true) {
                $key === 'pest_has' => in_array($value, $result['pest_groups'], true),
                $key === 'e2e_has' => in_array($value, $result['e2e_dirs'], true),
                default => $result[$key] === $value,
            };
            if (!$ok) {
                $failures++;
                fwrite(STDERR, "FAIL $file [$key expected ".json_encode($value).'] got '.json_encode($result)."\n");
            }
        }
    }

    $total = count($cases);
    if ($failures === 0) {
        echo "self-test: OK ($total cases)\n";

        return 0;
    }
    echo "self-test: $failures failure(s) in $total cases\n";

    return 1;
}

if (isset($opts['self-test'])) {
    exit(runSelfTest($root));
}

$groups = scanGroups($root);
$e2eDirs = scanE2EDirs($root);

if (isset($opts['files'])) {
    $raw = file_get_contents($opts['files']);
} else {
    $raw = stream_get_contents(STDIN);
}

$files = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $raw) ?: [])));
if ($files === []) {
    // Empty diff: run nothing (docs/no-op), not FULL.
    echo json_encode(['full_pest' => false, 'full_e2e' => false, 'pest_groups' => [], 'e2e_dirs' => [], 'ignored' => [], 'reasons' => ['no_changes']], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    exit(0);
}

$result = mapFiles($root, $files, $groups, $e2eDirs);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
exit(0);
