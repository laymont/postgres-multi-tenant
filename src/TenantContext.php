<?php

declare(strict_types=1);

namespace Laymont\PostgresMultiTenant;

final class TenantContext
{
    private string $schema;

    public function __construct(string $schema = 'public')
    {
        $schema = trim($schema);

        $this->schema = $schema === '' ? 'public' : $schema;
    }

    public function set(string $schema): void
    {
        $schema = trim($schema);

        $this->schema = $schema === '' ? 'public' : $schema;
    }

    public function get(): string
    {
        return $this->schema;
    }

    public function isLandlord(): bool
    {
        return $this->schema === '' || $this->schema === 'public';
    }
}
