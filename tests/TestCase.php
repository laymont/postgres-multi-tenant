<?php

declare(strict_types=1);

namespace Laymont\PostgresMultiTenant\Tests;

use Dotenv\Dotenv;
use Laymont\PostgresMultiTenant\Providers\PostgresMultiTenantServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PostgresMultiTenantServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $loaded = Dotenv::createImmutable(dirname(__DIR__), '.env.testing')->safeLoad();

        foreach ($loaded as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if (getenv($key) !== false) {
                continue;
            }

            putenv($key.'='.$value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        $app['config']->set('database.default', 'pgsql');

        $app['config']->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => (string) (getenv('DB_HOST') ?: '127.0.0.1'),
            'port' => (string) (getenv('DB_PORT') ?: '5432'),
            'database' => (string) (getenv('DB_DATABASE') ?: 'postgres_multi_tenant_test'),
            'username' => (string) (getenv('DB_USERNAME') ?: 'postgres'),
            'password' => (string) (getenv('DB_PASSWORD') ?: ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ]);
    }
}
