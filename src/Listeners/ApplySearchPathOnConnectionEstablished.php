<?php

declare(strict_types=1);

namespace Laymont\PostgresMultiTenant\Listeners;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Database\PostgresConnection;
use Laymont\PostgresMultiTenant\Services\TenantSchemaManager;

final class ApplySearchPathOnConnectionEstablished
{
    public function __construct(
        private readonly TenantSchemaManager $schemaManager,
        private readonly ConfigRepository $config,
    ) {}

    public function handle(ConnectionEstablished $event): void
    {
        if (! $event->connection instanceof PostgresConnection) {
            return;
        }

        if ($event->connection->getName() !== $this->schemaManager->connectionName()) {
            return;
        }

        $this->schemaManager->applySearchPath();

        if (! (bool) $this->config->get('postgres-multi-tenant.apply_search_path_before_query', true)) {
            return;
        }

        $event->connection->beforeExecuting(function (string $query): void {
            if (preg_match('/^\s*set\s+search_path\b/i', $query) === 1) {
                return;
            }

            $this->schemaManager->applySearchPath();
        });
    }
}
