<?php

declare(strict_types=1);

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Laymont\PostgresMultiTenant\Contracts\SearchPathApplier;
use Laymont\PostgresMultiTenant\Contracts\TenantResolver;
use Laymont\PostgresMultiTenant\Http\Middleware\SwitchTenantSchema;
use Laymont\PostgresMultiTenant\Services\TenantSchemaManager;
use Laymont\PostgresMultiTenant\Support\SearchPathBuilder;
use Laymont\PostgresMultiTenant\TenantContext;
use Mockery as m;
use Symfony\Component\HttpFoundation\Response;

it('sets landlord when tenant is not resolved', function () {
    $resolver = m::mock(TenantResolver::class);
    $resolver->shouldReceive('resolve')->once()->andReturnNull();

    $applier = m::mock(SearchPathApplier::class);
    $applier->shouldReceive('purgeAndReconnect')->once()->with('pgsql');
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

    app()->instance('config', new ConfigRepository([
        'postgres-multi-tenant' => [
            'unresolved_tenant_mode' => 'landlord',
            'reset_to_landlord_on_terminate' => true,
        ],
    ]));

    $middleware = new SwitchTenantSchema($manager, $resolver);

    $request = Request::create('/', 'GET');

    $response = $middleware->handle($request, fn () => new Response('ok'));

    expect($response->getContent())->toBe('ok');
});

it('resets to landlord on terminate', function () {
    $resolver = m::mock(TenantResolver::class);
    $resolver->shouldReceive('resolve')->once()->andReturnNull();

    $applier = m::mock(SearchPathApplier::class);
    $applier->shouldReceive('purgeAndReconnect')->twice()->with('pgsql');
    $applier->shouldReceive('setSearchPath')->twice()->with('pgsql', m::type('string'));

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

    app()->instance('config', new ConfigRepository([
        'postgres-multi-tenant' => [
            'unresolved_tenant_mode' => 'landlord',
            'reset_to_landlord_on_terminate' => true,
        ],
    ]));

    $middleware = new SwitchTenantSchema($manager, $resolver);

    $request = Request::create('/', 'GET');
    $response = $middleware->handle($request, fn () => new Response('ok'));

    $middleware->terminate($request, $response);

    expect(true)->toBeTrue();
});
