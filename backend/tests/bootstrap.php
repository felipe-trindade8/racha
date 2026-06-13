<?php

/*
 * Guarantee the test suite never runs against the real (MySQL) development
 * database.
 *
 * docker-compose.yml sets DB_* as real container environment variables, which
 * PHP exposes via $_SERVER. Laravel's env repository reads $_SERVER before
 * $_ENV, and PHPUnit's <env> overrides (even with force="true") only rewrite
 * $_ENV/putenv — never $_SERVER. As a result the sqlite override in phpunit.xml
 * is shadowed and RefreshDatabase would drop every table in the dev database.
 *
 * Forcing the sqlite in-memory connection here, before the framework boots and
 * across all three lookup sources, keeps the dev data safe. The guard in
 * tests/Feature/DatabaseIsolationTest.php asserts this stays effective.
 */
foreach (['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:'] as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require __DIR__.'/../vendor/autoload.php';
