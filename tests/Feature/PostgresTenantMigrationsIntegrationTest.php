<?php

declare(strict_types=1);

use Illuminate\Database\DatabaseManager;
use Laymont\PostgresMultiTenant\Services\TenantMigrationRunner;
use Laymont\PostgresMultiTenant\Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    /** @var DatabaseManager $db */
    $db = $this->app->make(DatabaseManager::class);

    $db->connection('pgsql')->statement('DROP SCHEMA IF EXISTS tenant_mig CASCADE');
    $db->connection('pgsql')->statement('CREATE SCHEMA tenant_mig');

    $db->connection('pgsql')->statement('DROP TABLE IF EXISTS public.migrations');
});

afterEach(function () {
    /** @var DatabaseManager $db */
    $db = $this->app->make(DatabaseManager::class);

    $db->connection('pgsql')->statement('DROP SCHEMA IF EXISTS tenant_mig CASCADE');
    $db->connection('pgsql')->statement('DROP TABLE IF EXISTS public.migrations');
});

it('creates migrations table inside tenant schema when migrating in tenant context', function () {
    /** @var TenantMigrationRunner $runner */
    $runner = $this->app->make(TenantMigrationRunner::class);

    /** @var DatabaseManager $db */
    $db = $this->app->make(DatabaseManager::class);

    $exit = $runner->run('tenant_mig', [
        '--path' => 'tests/Fixtures/migrations',
        '--force' => true,
    ]);

    expect($exit)->toBe(0);

    $existsInTenant = $db->connection('pgsql')->selectOne(
        "select 1 as ok from information_schema.tables where table_schema = 'tenant_mig' and table_name = 'migrations' limit 1",
    );

    expect($existsInTenant)->not->toBeNull();
});
