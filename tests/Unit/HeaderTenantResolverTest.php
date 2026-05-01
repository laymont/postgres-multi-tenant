<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Laymont\PostgresMultiTenant\Resolvers\HeaderTenantResolver;

it('resolves schema from header', function () {
    $resolver = new HeaderTenantResolver('X-Tenant-Schema');

    $request = Request::create('/', 'GET', server: [
        'HTTP_X_TENANT_SCHEMA' => 'tenant_acme',
    ]);

    expect($resolver->resolve($request))->toBe('tenant_acme');
});

it('returns null when header is missing or empty', function () {
    $resolver = new HeaderTenantResolver('X-Tenant-Schema');

    $request = Request::create('/', 'GET');
    expect($resolver->resolve($request))->toBeNull();

    $request = Request::create('/', 'GET', server: [
        'HTTP_X_TENANT_SCHEMA' => ' ',
    ]);
    expect($resolver->resolve($request))->toBeNull();
});
