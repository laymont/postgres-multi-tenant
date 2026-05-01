<?php

declare(strict_types=1);

use Laymont\PostgresMultiTenant\Tests\TestCase;
use Pest\TestSuite;

TestSuite::rootPath(__DIR__);

uses(TestCase::class)->in('tests');
