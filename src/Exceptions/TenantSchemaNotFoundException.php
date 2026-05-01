<?php

declare(strict_types=1);

namespace Laymont\PostgresMultiTenant\Exceptions;

use RuntimeException;

final class TenantSchemaNotFoundException extends RuntimeException
{
    public function __construct(public readonly string $schema)
    {
        parent::__construct('Tenant schema does not exist: '.$schema);
    }
}
