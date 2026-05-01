<?php

declare(strict_types=1);

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\PostgresConnection;
use Laymont\PostgresMultiTenant\Contracts\SearchPathApplier;
use Laymont\PostgresMultiTenant\Listeners\ApplySearchPathOnConnectionEstablished;
use Laymont\PostgresMultiTenant\Listeners\ApplySearchPathOnTransactionBeginning;
use Laymont\PostgresMultiTenant\Services\TenantSchemaManager;
use Laymont\PostgresMultiTenant\Support\SearchPathBuilder;
use Laymont\PostgresMultiTenant\TenantContext;
use Mockery as m;

it('applies applySearchPath on ConnectionEstablished for postgres and matching connection', function () {
    $applier = m::mock(SearchPathApplier::class);
    $applier->shouldReceive('setSearchPath')->once()->with('pgsql', m::type('string'));

    $manager = new TenantSchemaManager(
        new TenantContext,
        new ConfigRepository([
            'postgres-multi-tenant' => [
                'connection' => 'pgsql',
                'default_schema' => 'public',
                'schema_pattern' => '/^tenant_[a-z0-9_]{1,57}$/',
                'search_path_schemas' => ['{tenant}', 'public'],
                'enforce_schema_guardrail' => true,
                'apply_search_path_before_query' => false,
            ],
            'database' => [
                'connections' => [
                    'pgsql' => [
                        'search_path' => 'something_else',
                    ],
                ],
            ],
        ]),
        $applier,
        new SearchPathBuilder,
    );

    $connection = m::mock(PostgresConnection::class);
    $connection->shouldReceive('getName')->andReturn('pgsql');

    $listener = new ApplySearchPathOnConnectionEstablished($manager, new ConfigRepository([
        'postgres-multi-tenant' => [
            'apply_search_path_before_query' => false,
        ],
    ]));

    $listener->handle(new ConnectionEstablished($connection));

    expect(true)->toBeTrue();
});

it('applies SET LOCAL search_path on TransactionBeginning for postgres and matching connection', function () {
    $applier = m::mock(SearchPathApplier::class);
    $applier->shouldReceive('setLocalSearchPath')->once()->with('pgsql', '"tenant_acme", "public"');

    $manager = new TenantSchemaManager(
        new TenantContext('tenant_acme'),
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

    $connection = m::mock(PostgresConnection::class);
    $connection->shouldReceive('getName')->andReturn('pgsql');

    $listener = new ApplySearchPathOnTransactionBeginning($manager, $applier);

    $listener->handle(new TransactionBeginning($connection));

    expect(true)->toBeTrue();
});
