# laymont/postgres-multi-tenant

Este paquete proporciona una base para implementar **multi-tenancy schema-per-tenant** en **PostgreSQL** para proyectos Laravel **12/13**, con foco en buenas prácticas, tipado estricto y testabilidad.

## Objetivo

- Cambiar dinámicamente el `search_path` de PostgreSQL por request/job/command.
- Proveer una capa de resolución del tenant (domain/subdomain/header/path/etc.) sin acoplarlo a una app específica.
- Mantener el código del consumidor lo más limpio posible (SRP/DIP), exponiendo contratos claros.

## Enfoque

Este paquete implementa el patrón probado en producción para Postgres schema-per-tenant:

- **TenantContext**: estado del schema actual.
- **TenantSchemaManager**: aplica `search_path` (config + SQL) y permite volver a `public`.
- **Listeners**:
  - `ConnectionEstablished`: re-aplica `search_path` al reconectar.
  - `TransactionBeginning`: aplica `SET LOCAL search_path` al iniciar transacción.
- **Guardrail opcional**: validación de `current_schema()` vs schema esperado.

## Garantías anti-fuga (lo más importante)

- El `search_path` se aplica en conexión (`ConnectionEstablished`) y se re-sincroniza si se pierde.
- En cada transacción se aplica `SET LOCAL search_path` para reforzar aislamiento.
- El middleware puede ejecutar un **guardrail** que compara `current_schema()` con el schema esperado.
- En entornos con workers persistentes (ej: Octane), el middleware hace **reset a `public`** en `terminate()`.
- Opcionalmente, antes de **cada query** se re-aplica el `search_path` esperado (auto-healing) para proteger contra modificaciones fuera de banda.

Config:

- `apply_search_path_before_query`: `true` (default)

## Resolución de Tenant (baja fricción)

Por defecto, el paquete resuelve el schema desde el header:

- `X-Tenant-Schema: tenant_acme`

Puedes cambiarlo definiendo `postgres-multi-tenant.tenant_resolver` con una clase que implemente `Laymont\PostgresMultiTenant\Contracts\TenantResolver`.

Resolvers incluidos:

- `Laymont\PostgresMultiTenant\Resolvers\HeaderTenantResolver`
- `Laymont\PostgresMultiTenant\Resolvers\AuthUserTenantResolver`

## Middleware recomendado

Middleware incluido:

- `Laymont\PostgresMultiTenant\Http\Middleware\SwitchTenantSchema`

Config clave:

- `unresolved_tenant_mode`: `landlord` (default) o `deny`
- `reset_to_landlord_on_terminate`: `true` (default)

## Jobs (Laravel Queue)

Este paquete se apega al marco de Laravel:

- La **persistencia de la cola** (ej: tabla `jobs`) debe vivir en el **schema global** (`public`).
- La **ejecución** del job se hace en el **schema del tenant** (cambiando `search_path`) y luego se resetea a `public`.

Para eso se incluye un Job Middleware compatible con Laravel:

- `Laymont\PostgresMultiTenant\Queue\Middleware\RunJobInTenantSchema`

Y un contrato para jobs tenant-aware:

- `Laymont\PostgresMultiTenant\Contracts\TenantAwareJob` (método `tenantSchema(): ?string`)

## Migraciones (Laravel Migrations)

Este paquete también se apega al marco de Laravel:

- Laravel controla el catálogo global con su tabla `migrations` en `public`.
- Para cada tenant, al ejecutar migraciones con `search_path` apuntando al schema del tenant, Laravel crea/usa una tabla `migrations` **dentro del schema del tenant**.

Comando incluido:

- `php artisan tenant:migrate {schema}`

## Compatibilidad

- PHP: `^8.2`
- Laravel: `^12.0|^13.0`

## Instalación

```bash
composer require laymont/postgres-multi-tenant
```

## Implementación (proyecto nuevo)

### 1) Publica la configuración

```bash
php artisan vendor:publish --tag="laymont-postgres-multi-tenant-config"
```

### 2) Configura el comportamiento del paquete

Edita `config/postgres-multi-tenant.php`.

Recomendado (anti-fuga por defecto):

- `connection`: `pgsql`
- `search_path_schemas`: `['{tenant}', 'public']`
- `enforce_schema_guardrail`: `true`
- `reset_to_landlord_on_terminate`: `true`
- `apply_search_path_before_query`: `true`

Nota: el paquete prioriza `config()` (cache-safe). En producción debes usar `php artisan config:cache` como en cualquier app Laravel.

### 3) Decide cómo resolver el tenant

Opción recomendada (API):

- Header `X-Tenant-Schema: tenant_acme`

Si necesitas otra estrategia, define `tenant_resolver` con una clase que implemente:

- `Laymont\PostgresMultiTenant\Contracts\TenantResolver`

### 4) Registra el middleware

El middleware incluido es:

- `Laymont\PostgresMultiTenant\Http\Middleware\SwitchTenantSchema`

Ejemplo (Laravel 12/13): agrega el middleware donde tu aplicación registre middleware (por ejemplo, a nivel de rutas o grupo de rutas).

Recomendación:

- Aplica este middleware **solo** a las rutas tenant-aware (no necesariamente a todo el proyecto).

### 5) Define el contrato de “schema name”

Recomendación: usa un patrón de schemas consistente (por ejemplo `tenant_acme`) y mantén el regex en:

- `schema_pattern`

Esto es una protección contra esquemas inválidos y reduce vectores de fuga.

## Implementación (proyecto existente)

### 1) Asegura tu conexión `pgsql`

Verifica que tu `config/database.php` (o la configuración equivalente) use PostgreSQL y que la conexión que vaya a operar en modo tenant sea la misma que definiste en:

- `postgres-multi-tenant.connection`

### 2) Evita fugas por workers persistentes

Si usas Octane o workers persistentes:

- Mantén `reset_to_landlord_on_terminate=true`
- Mantén `apply_search_path_before_query=true`

### 3) Adopta gradualmente

Puedes empezar aplicando el middleware solo a un subset de endpoints tenant-aware.

## Jobs (Laravel Queue) - implementación

Principio: **cola global** (tabla `jobs` en `public`) + **ejecución tenant-aware**.

Para que un job sea tenant-aware, implementa:

- `Laymont\PostgresMultiTenant\Contracts\TenantAwareJob`

Y agrega el middleware al job:

- `Laymont\PostgresMultiTenant\Queue\Middleware\RunJobInTenantSchema`

El middleware asegura:

- switch a tenant
- guardrail
- reset a landlord en `finally`

## Migraciones - implementación

Para migrar un schema tenant:

```bash
php artisan tenant:migrate tenant_acme
```

Esto ejecuta `migrate` de Laravel dentro del contexto del tenant, permitiendo que Laravel cree y use:

- `tenant_acme.migrations` (tabla por tenant)

## Verificación rápida (debug)

En Postgres puedes verificar el schema actual con:

```sql
select current_schema();
```

En runtime, el guardrail del paquete compara `current_schema()` contra el schema esperado cuando está habilitado.

## Configuración

Publicar configuración:

```bash
php artisan vendor:publish --tag="laymont-postgres-multi-tenant-config"
```

Esto creará `config/postgres-multi-tenant.php`.

## Uso

Ver secciones:

- Implementación (proyecto nuevo)
- Implementación (proyecto existente)
- Jobs (Laravel Queue)
- Migraciones (Laravel Migrations)

## Licencia

MIT.
