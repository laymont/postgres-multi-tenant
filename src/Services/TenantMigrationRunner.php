<?php

declare(strict_types=1);

namespace Laymont\PostgresMultiTenant\Services;

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

final class TenantMigrationRunner
{
    public function __construct(
        private readonly ConsoleKernel $kernel,
        private readonly TenantSchemaManager $schemaManager,
    ) {}

    /**
     * @param  array<string, mixed>  $migrateOptions
     */
    public function run(string $schema, array $migrateOptions = []): int
    {
        $this->schemaManager->setTenant($schema);
        $this->schemaManager->enforceCurrentSchemaMatch();

        $params = array_merge([
            '--database' => $this->schemaManager->connectionName(),
        ], $migrateOptions);

        try {
            return (int) $this->kernel->call('migrate', $params);
        } finally {
            $this->schemaManager->setLandlord();
        }
    }
}
