<?php

declare(strict_types=1);

namespace Laymont\PostgresMultiTenant\Providers;

use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Support\ServiceProvider;
use Laymont\PostgresMultiTenant\Console\Commands\TenantMigrateCommand;
use Laymont\PostgresMultiTenant\Contracts\SearchPathApplier;
use Laymont\PostgresMultiTenant\Contracts\TenantResolver;
use Laymont\PostgresMultiTenant\Database\LaravelSearchPathApplier;
use Laymont\PostgresMultiTenant\Listeners\ApplySearchPathOnConnectionEstablished;
use Laymont\PostgresMultiTenant\Listeners\ApplySearchPathOnTransactionBeginning;
use Laymont\PostgresMultiTenant\Resolvers\HeaderTenantResolver;
use Laymont\PostgresMultiTenant\Services\TenantSchemaManager;
use Laymont\PostgresMultiTenant\Services\TenantSchemaRunner;
use Laymont\PostgresMultiTenant\Support\SearchPathBuilder;
use Laymont\PostgresMultiTenant\TenantContext;

class PostgresMultiTenantServiceProvider extends ServiceProvider
{
    public const PUBLISH_GROUP = 'laymont-postgres-multi-tenant';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/postgres-multi-tenant.php', 'postgres-multi-tenant');

        $this->app->singleton(TenantContext::class);

        $this->app->singleton(SearchPathBuilder::class);

        $this->app->singleton(SearchPathApplier::class, LaravelSearchPathApplier::class);

        $this->app->singleton(TenantSchemaManager::class);

        $this->app->singleton(TenantSchemaRunner::class);

        $this->registerResolver();
    }

    public function boot(): void
    {
        $this->registerPublishing();

        $this->registerListeners();

        $this->registerCommands();
    }

    protected function registerListeners(): void
    {
        $this->app['events']->listen(ConnectionEstablished::class, ApplySearchPathOnConnectionEstablished::class);

        $this->app['events']->listen(TransactionBeginning::class, ApplySearchPathOnTransactionBeginning::class);
    }

    protected function registerResolver(): void
    {
        $resolver = config('postgres-multi-tenant.tenant_resolver');

        if (is_string($resolver) && $resolver !== '') {
            $this->app->singleton(TenantResolver::class, $resolver);

            return;
        }

        $this->app->singleton(TenantResolver::class, static fn () => new HeaderTenantResolver);
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            TenantMigrateCommand::class,
        ]);
    }

    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/../../config/postgres-multi-tenant.php' => config_path('postgres-multi-tenant.php'),
        ], self::PUBLISH_GROUP.'-config');
    }
}
