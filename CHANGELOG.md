# CHANGELOG

Todas las modificaciones relevantes de este paquete se documentarán en este archivo.

## [Unreleased]

### Nuevo
- TenantContext, TenantSchemaManager, TenantSchemaRunner.
- SearchPathApplier (LaravelSearchPathApplier) y SearchPathBuilder.
- Listeners para persistencia del `search_path` y `SET LOCAL search_path` en transacciones.
- Tests unitarios para builder/context/validación.
- Resolución de tenant (TenantResolver) con resolvers incluidos (Header/AuthUser).
- Middleware SwitchTenantSchema con reset a landlord en terminate().
- Job Middleware RunJobInTenantSchema (Laravel Queue) y contrato TenantAwareJob.
- Comando tenant:migrate para migraciones por schema (Laravel Migrator).
- Tests de wiring del ServiceProvider y tests de integración Postgres (search_path y migraciones por schema).
- Auto-healing anti-fuga: re-aplicación de `search_path` antes de cada query (configurable).

## [0.1.0] - 2026-05-01

### Nuevo
- Estructura base del paquete.
