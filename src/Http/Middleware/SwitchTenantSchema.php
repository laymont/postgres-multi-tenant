<?php

declare(strict_types=1);

namespace Laymont\PostgresMultiTenant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laymont\PostgresMultiTenant\Contracts\TenantResolver;
use Laymont\PostgresMultiTenant\Exceptions\TenantNotResolvedException;
use Laymont\PostgresMultiTenant\Exceptions\TenantSchemaNotFoundException;
use Laymont\PostgresMultiTenant\Services\TenantSchemaManager;
use Symfony\Component\HttpFoundation\Response;

final class SwitchTenantSchema
{
    public function __construct(
        private readonly TenantSchemaManager $schemaManager,
        private readonly ?TenantResolver $resolver = null,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $mode = (string) config('postgres-multi-tenant.unresolved_tenant_mode', 'landlord');

        $resolver = $this->resolver;

        if (! $resolver) {
            $resolver = app(TenantResolver::class);
        }

        $schema = $resolver->resolve($request);

        if ($schema === null) {
            if ($mode === 'deny') {
                throw new TenantNotResolvedException('Tenant could not be resolved.');
            }

            $this->schemaManager->setLandlord();

            return $next($request);
        }

        try {
            $this->schemaManager->setTenant($schema);
        } catch (TenantSchemaNotFoundException $e) {
            if ($mode === 'deny') {
                throw $e;
            }

            $this->schemaManager->setLandlord();

            return $next($request);
        }

        $this->schemaManager->enforceCurrentSchemaMatch();

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! (bool) config('postgres-multi-tenant.reset_to_landlord_on_terminate', true)) {
            return;
        }

        $this->schemaManager->setLandlord();
    }
}
