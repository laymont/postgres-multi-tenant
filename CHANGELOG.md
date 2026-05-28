# CHANGELOG

Todas las modificaciones relevantes de este paquete se documentarán en este archivo.

## [Unreleased]

## [0.2.0] - 2026-05-28

### Seguridad
- Actualizado dependencias de Laravel 13 para parches de seguridad de Symfony
- Actualizado paquetes Symfony vulnerables:
  - symfony/polyfill-intl-idn (v1.37.0 -> v1.38.1) - CVE-2026-46644
  - symfony/routing (v7.4.9 -> v7.4.13) - CVE-2026-48784, CVE-2026-45065
  - symfony/yaml (v7.4.8 -> v7.4.13) - CVE-2026-45304, CVE-2026-45305, CVE-2026-45133
- Actualizado orchestra/testbench-core (v11.3.1 -> v11.3.3)

### Cambios
- Actualizado composer.lock con 36 actualizaciones de dependencias
- Composer audit: No vulnerabilities found

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
