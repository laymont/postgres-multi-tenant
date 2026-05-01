<?php

declare(strict_types=1);

namespace Laymont\PostgresMultiTenant\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Laymont\PostgresMultiTenant\Contracts\SearchPathApplier;
use Laymont\PostgresMultiTenant\Exceptions\InvalidTenantSchemaException;
use Laymont\PostgresMultiTenant\Exceptions\TenantSchemaNotFoundException;
use Laymont\PostgresMultiTenant\Support\SearchPathBuilder;
use Laymont\PostgresMultiTenant\TenantContext;

final class TenantSchemaManager
{
    public function __construct(
        private readonly TenantContext $context,
        private readonly ConfigRepository $config,
        private readonly SearchPathApplier $applier,
        private readonly SearchPathBuilder $searchPathBuilder,
    ) {}

    public function setTenant(string $schema): void
    {
        $schema = $this->normalizeSchema($schema);

        if (! $this->isValidSchema($schema)) {
            throw new InvalidTenantSchemaException('Invalid tenant schema name.');
        }

        $this->context->set($schema);

        $this->applyToConfig($schema);

        $connection = $this->connectionName();

        $this->applier->purgeAndReconnect($connection);

        if (! $this->applier->schemaExists($connection, $schema)) {
            throw new TenantSchemaNotFoundException($schema);
        }

        $this->applier->setSearchPath($connection, $this->currentSearchPathSql());
    }

    public function setLandlord(): void
    {
        $schema = $this->defaultSchema();

        $this->context->set($schema);

        $this->applyToConfig($schema);

        $connection = $this->connectionName();

        $this->applier->purgeAndReconnect($connection);
        $this->applier->setSearchPath($connection, $this->currentSearchPathSql());
    }

    public function applyToConfig(string $schema): void
    {
        $schema = $this->normalizeSchema($schema);

        if ($schema === '' || $schema === 'public') {
            $schema = 'public';
        } else {
            if (! $this->isValidSchema($schema)) {
                throw new InvalidTenantSchemaException('Invalid tenant schema name.');
            }
        }

        $this->context->set($schema);

        $this->config->set(
            'database.connections.'.$this->connectionName().'.search_path',
            $this->unquotedSearchPathForConfig($schema),
        );
    }

    public function currentSearchPathSql(): string
    {
        return $this->searchPathBuilder->build(
            $this->context->get(),
            $this->searchPathSchemas(),
        );
    }

    public function applySearchPath(): void
    {
        $connection = $this->connectionName();

        $this->applier->setSearchPath($connection, $this->currentSearchPathSql());
    }

    public function unquotedSearchPathForConfig(?string $schema = null): string
    {
        $schema = $schema === null ? $this->context->get() : $schema;
        $schema = $this->normalizeSchema($schema);

        if ($schema === '' || $schema === 'public') {
            return 'public';
        }

        $parts = [];
        foreach ($this->searchPathSchemas() as $component) {
            $component = trim($component);
            if ($component === '') {
                continue;
            }

            if ($component === '{tenant}') {
                $component = $schema;
            }

            $parts[] = $component;
        }

        $parts = array_values(array_unique($parts));

        if ($parts === []) {
            return 'public';
        }

        return implode(', ', $parts);
    }

    public function ensureExpectedSearchPath(): void
    {
        $connection = $this->connectionName();

        $expected = $this->unquotedSearchPathForConfig();

        $configured = (string) $this->config->get(
            'database.connections.'.$connection.'.search_path',
            'public',
        );
        $configured = trim($configured) === '' ? 'public' : trim($configured);

        if ($configured === $expected) {
            return;
        }

        $this->applier->setSearchPath($connection, $this->currentSearchPathSql());

        $this->config->set('database.connections.'.$connection.'.search_path', $expected);
    }

    public function enforceCurrentSchemaMatch(): void
    {
        if (! $this->config->get('postgres-multi-tenant.enforce_schema_guardrail', true)) {
            return;
        }

        $expectedSchema = $this->normalizeSchema($this->context->get());

        if ($expectedSchema === '' || $expectedSchema === 'public') {
            return;
        }

        $current = $this->applier->currentSchema($this->connectionName());

        if ($current === null) {
            throw new InvalidTenantSchemaException('Unable to resolve current_schema().');
        }

        if ($current !== $expectedSchema) {
            throw new InvalidTenantSchemaException('Tenant context mismatch.');
        }
    }

    public function connectionName(): string
    {
        return (string) $this->config->get('postgres-multi-tenant.connection', 'pgsql');
    }

    /**
     * @return array<int, string>
     */
    private function searchPathSchemas(): array
    {
        $schemas = $this->config->get('postgres-multi-tenant.search_path_schemas', ['{tenant}', 'public']);

        return is_array($schemas) ? array_values($schemas) : ['{tenant}', 'public'];
    }

    private function defaultSchema(): string
    {
        $schema = (string) $this->config->get('postgres-multi-tenant.default_schema', 'public');
        $schema = $this->normalizeSchema($schema);

        return $schema === '' ? 'public' : $schema;
    }

    private function schemaPattern(): string
    {
        return (string) $this->config->get('postgres-multi-tenant.schema_pattern', '/^[a-z0-9_]{1,63}$/');
    }

    private function normalizeSchema(string $schema): string
    {
        return trim($schema) === '' ? 'public' : trim($schema);
    }

    private function isValidSchema(string $schema): bool
    {
        return preg_match($this->schemaPattern(), $schema) === 1;
    }
}
