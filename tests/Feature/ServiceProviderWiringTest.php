<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Laymont\PostgresMultiTenant\Contracts\TenantResolver;
use Laymont\PostgresMultiTenant\Resolvers\HeaderTenantResolver;
use Laymont\PostgresMultiTenant\Tests\TestCase;

uses(TestCase::class);

it('binds a default TenantResolver', function () {
    $resolver = $this->app->make(TenantResolver::class);

    expect($resolver)->toBeInstanceOf(HeaderTenantResolver::class);
});

it('registers the tenant:migrate command in console context', function () {
    /** @var ConsoleKernel $kernel */
    $kernel = $this->app->make(ConsoleKernel::class);

    $commands = $kernel->all();

    expect($commands)->toHaveKey('tenant:migrate');
});
