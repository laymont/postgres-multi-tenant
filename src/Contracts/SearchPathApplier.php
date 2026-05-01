<?php

declare(strict_types=1);

namespace Laymont\PostgresMultiTenant\Contracts;

interface SearchPathApplier
{
    public function setSearchPath(string $connectionName, string $searchPathSql): void;

    public function setLocalSearchPath(string $connectionName, string $searchPathSql): void;

    public function currentSchema(string $connectionName): ?string;

    public function schemaExists(string $connectionName, string $schema): bool;

    public function purgeAndReconnect(string $connectionName): void;
}
