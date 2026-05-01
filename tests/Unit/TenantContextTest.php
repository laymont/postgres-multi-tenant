<?php

declare(strict_types=1);

use Laymont\PostgresMultiTenant\TenantContext;

it('defaults to public and treats empty as public', function () {
    $context = new TenantContext('');

    expect($context->get())->toBe('public');
    expect($context->isLandlord())->toBeTrue();
});

it('stores the current schema', function () {
    $context = new TenantContext;

    $context->set('tenant_acme');

    expect($context->get())->toBe('tenant_acme');
    expect($context->isLandlord())->toBeFalse();
});
