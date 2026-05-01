<?php

declare(strict_types=1);

use Illuminate\Config\Repository as ConfigRepository;
use Laymont\PostgresMultiTenant\Contracts\SearchPathApplier;
use Laymont\PostgresMultiTenant\Exceptions\InvalidTenantSchemaException;
use Laymont\PostgresMultiTenant\Exceptions\TenantSchemaNotFoundException;
use Laymont\PostgresMultiTenant\Services\TenantSchemaManager;
use Laymont\PostgresMultiTenant\Support\SearchPathBuilder;
use Laymont\PostgresMultiTenant\TenantContext;
use Mockery as m;

function makeTenantSchemaManager(SearchPathApplier $applier, array $overrides = []): TenantSchemaManager
{
    $config = array_replace_recursive([
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
    ], $overrides);

    return new TenantSchemaManager(new TenantContext, new ConfigRepository($config), $applier, new SearchPathBuilder);
}

it('throws when schema does not exist', function () {
    $applier = m::mock(SearchPathApplier::class);

    $applier->shouldReceive('purgeAndReconnect')->once()->with('pgsql');
    $applier->shouldReceive('schemaExists')->once()->with('pgsql', 'tenant_acme')->andReturnFalse();

    $manager = makeTenantSchemaManager($applier);

    expect(fn () => $manager->setTenant('tenant_acme'))
        ->toThrow(TenantSchemaNotFoundException::class);
});

it('ensureExpectedSearchPath updates config and applies sql when configured differs', function () {
    $applier = m::mock(SearchPathApplier::class);

    $applier->shouldReceive('setSearchPath')->once()->with('pgsql', m::type('string'));

    $config = new ConfigRepository([
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
                    'search_path' => 'something_else',
                ],
            ],
        ],
    ]);

    $manager = new TenantSchemaManager(new TenantContext, $config, $applier, new SearchPathBuilder);

    $manager->ensureExpectedSearchPath();

    expect($config->get('database.connections.pgsql.search_path'))->toBe('public');
});

it('enforceCurrentSchemaMatch throws when current schema is null', function () {
    $applier = m::mock(SearchPathApplier::class);

    $applier->shouldReceive('currentSchema')->once()->with('pgsql')->andReturnNull();

    $manager = makeTenantSchemaManager($applier);

    $manager->applyToConfig('tenant_acme');

    expect(fn () => $manager->enforceCurrentSchemaMatch())
        ->toThrow(InvalidTenantSchemaException::class);
});

it('enforceCurrentSchemaMatch throws when current schema mismatches expected', function () {
    $applier = m::mock(SearchPathApplier::class);

    $applier->shouldReceive('currentSchema')->once()->with('pgsql')->andReturn('tenant_other');

    $manager = makeTenantSchemaManager($applier);

    $manager->applyToConfig('tenant_acme');

    expect(fn () => $manager->enforceCurrentSchemaMatch())
        ->toThrow(InvalidTenantSchemaException::class);
});
