<?php

namespace Tests\Concerns;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;

/**
 * Seed the demo dataset once per test run instead of once per test.
 *
 * RefreshDatabase migrates once, but seeding in a beforeEach repeats the full
 * DatabaseSeeder for every test. Here the seed runs once, outside the per-test
 * transaction, and each test then only wraps its own writes in a rollback.
 * Read-only tests therefore see the seeded data at no extra cost.
 */
trait RefreshDatabaseSeededOnce
{
    use RefreshDatabase;

    protected static bool $demoSeeded = false;

    protected function refreshTestDatabase()
    {
        if (! RefreshDatabaseState::$migrated || ! static::$demoSeeded) {
            $this->migrateDatabases();

            $this->app[Kernel::class]->setArtisan(null);

            $this->seed();

            RefreshDatabaseState::$migrated = true;
            static::$demoSeeded = true;
        }

        $this->beginDatabaseTransaction();
    }
}
