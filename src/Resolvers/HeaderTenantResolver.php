<?php

declare(strict_types=1);

namespace Laymont\PostgresMultiTenant\Resolvers;

use Illuminate\Http\Request;
use Laymont\PostgresMultiTenant\Contracts\TenantResolver;

final class HeaderTenantResolver implements TenantResolver
{
    public function __construct(private readonly string $headerName = 'X-Tenant-Schema') {}

    public function resolve(Request $request): ?string
    {
        $schema = (string) $request->header($this->headerName, '');
        $schema = trim($schema);

        return $schema === '' ? null : $schema;
    }
}
