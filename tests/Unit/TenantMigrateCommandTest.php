<?php

declare(strict_types=1);

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Laymont\PostgresMultiTenant\Contracts\SearchPathApplier;
use Laymont\PostgresMultiTenant\Services\TenantMigrationRunner;
use Laymont\PostgresMultiTenant\Services\TenantSchemaManager;
use Laymont\PostgresMultiTenant\Support\SearchPathBuilder;
use Laymont\PostgresMultiTenant\TenantContext;
use Mockery as m;

it('runs laravel migrate using console kernel within tenant schema and resets to landlord', function () {
    $kernel = m::mock(ConsoleKernel::class);
    $kernel->shouldReceive('call')->once()->with('migrate', m::on(function (array $params) {
        return ($params['--database'] ?? null) === 'pgsql';
    }))->andReturn(0);

    $applier = m::mock(SearchPathApplier::class);

    $applier->shouldReceive('purgeAndReconnect')->twice()->with('pgsql');
    $applier->shouldReceive('schemaExists')->once()->with('pgsql', 'tenant_acme')->andReturnTrue();
    $applier->shouldReceive('setSearchPath')->twice()->with('pgsql', m::type('string'));
    $applier->shouldReceive('currentSchema')->once()->with('pgsql')->andReturn('tenant_acme');

    $manager = new TenantSchemaManager(
        new TenantContext,
        new ConfigRepository([
            'postgres-multi-tenant' => [
                'connection' => 'pgsql',
                'default_schema' => 'public',
                'schema_pattern' => '/^tenant_[a-z0-9_]{1,57}$/',
                'search_path_schemas' => ['{tenant}', 'public'],
                'enforce_schema_guardrail' => true,
            ],
            'database' => [
                'connections' => [
                    'pgsql' => [
                        'search_path' => 'public',
                    ],
                ],
            ],
        ]),
        $applier,
        new SearchPathBuilder,
    );

    $runner = new TenantMigrationRunner($kernel, $manager);

    $exitCode = $runner->run('tenant_acme');

    expect($exitCode)->toBe(0);
});
