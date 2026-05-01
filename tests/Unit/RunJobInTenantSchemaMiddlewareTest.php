<?php

declare(strict_types=1);

use Illuminate\Config\Repository as ConfigRepository;
use Laymont\PostgresMultiTenant\Contracts\SearchPathApplier;
use Laymont\PostgresMultiTenant\Contracts\TenantAwareJob;
use Laymont\PostgresMultiTenant\Queue\Middleware\RunJobInTenantSchema;
use Laymont\PostgresMultiTenant\Services\TenantSchemaManager;
use Laymont\PostgresMultiTenant\Support\SearchPathBuilder;
use Laymont\PostgresMultiTenant\TenantContext;
use Mockery as m;

it('runs tenant-aware job inside tenant schema and resets to landlord', function () {
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

    $middleware = new RunJobInTenantSchema($manager);

    $job = new class implements TenantAwareJob
    {
        public function tenantSchema(): ?string
        {
            return 'tenant_acme';
        }
    };

    $result = $middleware->handle($job, fn () => 'ok');

    expect($result)->toBe('ok');
});

it('does nothing for jobs that are not tenant-aware', function () {
    $applier = m::mock(SearchPathApplier::class);

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

    $middleware = new RunJobInTenantSchema($manager);

    $job = new class {};

    $result = $middleware->handle($job, fn () => 'ok');

    expect($result)->toBe('ok');
});
