<?php

declare(strict_types=1);

use Illuminate\Config\Repository as ConfigRepository;
use Laymont\PostgresMultiTenant\Contracts\SearchPathApplier;
use Laymont\PostgresMultiTenant\Exceptions\InvalidTenantSchemaException;
use Laymont\PostgresMultiTenant\Services\TenantSchemaManager;
use Laymont\PostgresMultiTenant\Support\SearchPathBuilder;
use Laymont\PostgresMultiTenant\TenantContext;
use Mockery as m;

it('rejects invalid tenant schema name', function () {
    $context = new TenantContext;

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
                    'search_path' => 'public',
                ],
            ],
        ],
    ]);

    $applier = m::mock(SearchPathApplier::class);

    $applier
        ->shouldReceive('purgeAndReconnect')
        ->once()
        ->with('pgsql');

    $applier
        ->shouldReceive('setSearchPath')
        ->once()
        ->with('pgsql', m::type('string'));

    $manager = new TenantSchemaManager($context, $config, $applier, new SearchPathBuilder);

    $manager->setLandlord();

    expect(fn () => $manager->setTenant('invalid-schema!'))
        ->toThrow(InvalidTenantSchemaException::class);
});
