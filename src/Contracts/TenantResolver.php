<?php

declare(strict_types=1);

namespace Laymont\PostgresMultiTenant\Contracts;

use Illuminate\Http\Request;

interface TenantResolver
{
    public function resolve(Request $request): ?string;
}
