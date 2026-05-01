<?php

declare(strict_types=1);

namespace Laymont\PostgresMultiTenant\Support;

final class SearchPathBuilder
{
    /**
     * @param  array<int, string>  $components
     */
    public function build(string $schema, array $components): string
    {
        $schema = trim($schema);

        if ($schema === '') {
            $schema = 'public';
        }

        $schemas = [];

        foreach ($components as $component) {
            $component = trim($component);

            if ($component === '') {
                continue;
            }

            if ($component === '{tenant}') {
                $component = $schema;
            }

            $schemas[] = $this->quoteIdentifier($component);
        }

        $schemas = array_values(array_unique($schemas));

        if ($schemas === []) {
            return 'public';
        }

        return implode(', ', $schemas);
    }

    private function quoteIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return '"public"';
        }

        return '"'.str_replace('"', '""', $identifier).'"';
    }
}
