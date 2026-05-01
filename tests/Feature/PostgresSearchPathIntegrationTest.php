<?php

declare(strict_types=1);

use Illuminate\Database\DatabaseManager;
use Laymont\PostgresMultiTenant\Services\TenantSchemaManager;
use Laymont\PostgresMultiTenant\Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    /** @var DatabaseManager $db */
    $db = $this->app->make(DatabaseManager::class);

    $db->connection('pgsql')->statement('DROP SCHEMA IF EXISTS tenant_acme CASCADE');
    $db->connection('pgsql')->statement('CREATE SCHEMA tenant_acme');

    $db->connection('pgsql')->statement('DROP TABLE IF EXISTS public.entities');
    $db->connection('pgsql')->statement('CREATE TABLE public.entities (id int primary key, name text not null)');
    $db->connection('pgsql')->statement("INSERT INTO public.entities (id, name) VALUES (1, 'public')");

    $db->connection('pgsql')->statement('DROP TABLE IF EXISTS tenant_acme.entities');
    $db->connection('pgsql')->statement('CREATE TABLE tenant_acme.entities (id int primary key, name text not null)');
    $db->connection('pgsql')->statement("INSERT INTO tenant_acme.entities (id, name) VALUES (1, 'tenant')");
});

afterEach(function () {
    /** @var DatabaseManager $db */
    $db = $this->app->make(DatabaseManager::class);

    $db->connection('pgsql')->statement('DROP SCHEMA IF EXISTS tenant_acme CASCADE');
    $db->connection('pgsql')->statement('DROP TABLE IF EXISTS public.entities');
});

it('resolves unqualified table names using tenant search_path and then returns to public', function () {
    /** @var TenantSchemaManager $manager */
    $manager = $this->app->make(TenantSchemaManager::class);

    /** @var DatabaseManager $db */
    $db = $this->app->make(DatabaseManager::class);

    $manager->setLandlord();

    $public = $db->connection('pgsql')->selectOne('select name from entities where id = 1');
    expect($public->name)->toBe('public');

    $manager->setTenant('tenant_acme');

    $tenant = $db->connection('pgsql')->selectOne('select name from entities where id = 1');
    expect($tenant->name)->toBe('tenant');

    $manager->setLandlord();

    $publicAgain = $db->connection('pgsql')->selectOne('select name from entities where id = 1');
    expect($publicAgain->name)->toBe('public');
});

it('auto-heals when search_path is modified out-of-band (beforeExecuting guardrail)', function () {
    /** @var TenantSchemaManager $manager */
    $manager = $this->app->make(TenantSchemaManager::class);

    /** @var DatabaseManager $db */
    $db = $this->app->make(DatabaseManager::class);

    $manager->setTenant('tenant_acme');

    $db->connection('pgsql')->getPdo()->exec('SET search_path TO public');

    $tenant = $db->connection('pgsql')->selectOne('select name from entities where id = 1');
    expect($tenant->name)->toBe('tenant');
});

it('applies SET LOCAL search_path inside a transaction and does not leak afterwards', function () {
    /** @var TenantSchemaManager $manager */
    $manager = $this->app->make(TenantSchemaManager::class);

    /** @var DatabaseManager $db */
    $db = $this->app->make(DatabaseManager::class);

    $manager->setLandlord();

    $db->connection('pgsql')->beginTransaction();

    try {
        $manager->setTenant('tenant_acme');

        $tenant = $db->connection('pgsql')->selectOne('select name from entities where id = 1');
        expect($tenant->name)->toBe('tenant');
    } finally {
        $db->connection('pgsql')->rollBack();
        $manager->setLandlord();
    }

    $publicAgain = $db->connection('pgsql')->selectOne('select name from entities where id = 1');
    expect($publicAgain->name)->toBe('public');
});
