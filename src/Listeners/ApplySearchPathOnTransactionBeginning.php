<?php

declare(strict_types=1);

namespace Laymont\PostgresMultiTenant\Listeners;

use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\PostgresConnection;
use Laymont\PostgresMultiTenant\Contracts\SearchPathApplier;
use Laymont\PostgresMultiTenant\Services\TenantSchemaManager;

final class ApplySearchPathOnTransactionBeginning
{
    public function __construct(
        private readonly TenantSchemaManager $schemaManager,
        private readonly SearchPathApplier $applier,
    ) {}

    public function handle(TransactionBeginning $event): void
    {
        if (! $event->connection instanceof PostgresConnection) {
            return;
        }

        $connectionName = $this->schemaManager->connectionName();

        if ($event->connection->getName() !== $connectionName) {
            return;
        }

        $this->applier->setLocalSearchPath($connectionName, $this->schemaManager->currentSearchPathSql());
    }
}
