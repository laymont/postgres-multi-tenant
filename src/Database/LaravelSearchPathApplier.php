<?php

declare(strict_types=1);

namespace Laymont\PostgresMultiTenant\Database;

use Illuminate\Database\DatabaseManager;
use Laymont\PostgresMultiTenant\Contracts\SearchPathApplier;
use Throwable;

final class LaravelSearchPathApplier implements SearchPathApplier
{
    public function __construct(private readonly DatabaseManager $db) {}

    public function setSearchPath(string $connectionName, string $searchPathSql): void
    {
        $this->db->connection($connectionName)->statement('SET search_path TO '.$searchPathSql);
    }

    public function setLocalSearchPath(string $connectionName, string $searchPathSql): void
    {
        $this->db->connection($connectionName)->getPdo()?->exec('SET LOCAL search_path TO '.$searchPathSql);
    }

    public function currentSchema(string $connectionName): ?string
    {
        try {
            $row = $this->db->connection($connectionName)->selectOne('select current_schema() as schema');
            if (! is_object($row)) {
                return null;
            }

            $schema = (string) ($row->schema ?? '');

            return $schema === '' ? null : $schema;
        } catch (Throwable) {
            return null;
        }
    }

    public function schemaExists(string $connectionName, string $schema): bool
    {
        $row = $this->db->connection($connectionName)->selectOne(
            'select 1 as exists from pg_namespace where nspname = ? limit 1',
            [$schema],
        );

        return $row !== null;
    }

    public function purgeAndReconnect(string $connectionName): void
    {
        $this->db->purge($connectionName);
        $this->db->reconnect($connectionName);
    }
}
