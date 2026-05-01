<?php

declare(strict_types=1);

namespace Laymont\PostgresMultiTenant\Queue\Middleware;

use Laymont\PostgresMultiTenant\Contracts\TenantAwareJob;
use Laymont\PostgresMultiTenant\Services\TenantSchemaManager;
use Throwable;

final class RunJobInTenantSchema
{
    public function __construct(private readonly TenantSchemaManager $schemaManager) {}

    /**
     * Laravel Job Middleware signature.
     */
    public function handle(object $job, callable $next): mixed
    {
        if (! $job instanceof TenantAwareJob) {
            return $next($job);
        }

        $schema = $job->tenantSchema();

        if ($schema === null || trim($schema) === '' || trim($schema) === 'public') {
            return $next($job);
        }

        $this->schemaManager->setTenant($schema);
        $this->schemaManager->enforceCurrentSchemaMatch();

        try {
            return $next($job);
        } finally {
            try {
                $this->schemaManager->setLandlord();
            } catch (Throwable) {
                // no-op
            }
        }
    }
}
