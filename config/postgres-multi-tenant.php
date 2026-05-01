<?php

declare(strict_types=1);

return [
    'connection' => 'pgsql',

    'default_schema' => 'public',

    'tenant_schema_prefix' => 'tenant_',

    'schema_pattern' => '/^tenant_[a-z0-9_]{1,57}$/',

    'search_path_schemas' => ['{tenant}', 'public'],

    'enforce_schema_guardrail' => true,

    'tenant_resolver' => null,

    'unresolved_tenant_mode' => 'landlord',

    'reset_to_landlord_on_terminate' => true,

    'apply_search_path_before_query' => true,
];
