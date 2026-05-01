<?php

declare(strict_types=1);

namespace Laymont\PostgresMultiTenant\Console\Commands;

use Illuminate\Console\Command;
use Laymont\PostgresMultiTenant\Services\TenantMigrationRunner;

final class TenantMigrateCommand extends Command
{
    protected $signature = 'tenant:migrate
        {schema : Tenant schema name}
        {--path= : Paths to migration files}
        {--pretend : Dump the SQL queries that would be run}
        {--step : Force the migrations to be run so they can be rolled back individually}
        {--force : Force the operation to run when in production}
        {--seed : Indicates if the seed task should be re-run}
        {--seeder= : The class name of the root seeder}
    ';

    protected $description = 'Run migrations for a specific tenant schema (schema-per-tenant) using Laravel migrate.';

    public function __construct(
        private readonly TenantMigrationRunner $runner,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $schema = (string) $this->argument('schema');

        $params = [];

        foreach (['path', 'pretend', 'step', 'force', 'seed', 'seeder'] as $option) {
            $value = $this->option($option);

            if ($value === null || $value === false) {
                continue;
            }

            $params['--'.$option] = $value;
        }

        return $this->runner->run($schema, $params);
    }
}
