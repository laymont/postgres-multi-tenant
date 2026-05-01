<?php

declare(strict_types=1);

use Laymont\PostgresMultiTenant\Support\SearchPathBuilder;

it('builds quoted search_path with tenant first and public fallback', function () {
    $builder = new SearchPathBuilder;

    $sql = $builder->build('tenant_acme', ['{tenant}', 'public']);

    expect($sql)->toBe('"tenant_acme", "public"');
});

it('deduplicates schemas and ignores empty components', function () {
    $builder = new SearchPathBuilder;

    $sql = $builder->build('tenant_acme', ['{tenant}', 'public', 'public', '']);

    expect($sql)->toBe('"tenant_acme", "public"');
});
