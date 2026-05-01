<?php

declare(strict_types=1);

namespace Laymont\PostgresMultiTenant\Resolvers;

use Illuminate\Http\Request;
use Laymont\PostgresMultiTenant\Contracts\TenantResolver;

final class AuthUserTenantResolver implements TenantResolver
{
    public function __construct(private readonly string $userSchemaAttribute = 'schema_name') {}

    public function resolve(Request $request): ?string
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        $value = $user->{$this->userSchemaAttribute} ?? null;

        if (! is_string($value)) {
            return null;
        }

        $schema = trim($value);

        return $schema === '' ? null : $schema;
    }
}
