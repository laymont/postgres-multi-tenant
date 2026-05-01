<?php

declare(strict_types=1);

namespace Laymont\PostgresMultiTenant\Contracts;

interface TenantAwareJob
{
    public function tenantSchema(): ?string;
}
