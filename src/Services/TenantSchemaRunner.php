<?php

declare(strict_types=1);

namespace Laymont\PostgresMultiTenant\Services;

use Throwable;

final class TenantSchemaRunner
{
    public function __construct(private readonly TenantSchemaManager $schemaManager) {}

    /**
     * @template T
     *
     * @param  callable():T  $callback
     * @return T
     */
    public function runInTenant(string $schema, callable $callback)
    {
        $schema = trim($schema);

        if ($schema === '' || $schema === 'public') {
            return $callback();
        }

        $previous = $this->schemaManager->unquotedSearchPathForConfig();

        try {
            $this->schemaManager->setTenant($schema);

            return $callback();
        } finally {
            try {
                if ($previous === '' || $previous === 'public') {
                    $this->schemaManager->setLandlord();
                } else {
                    $this->schemaManager->applyToConfig('public');
                    $this->schemaManager->ensureExpectedSearchPath();
                }
            } catch (Throwable) {
                // no-op
            }
        }
    }
}
