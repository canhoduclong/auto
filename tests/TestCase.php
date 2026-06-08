<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $this->guardUnsafeTestingDatabase();

        parent::setUp();
    }

    private function guardUnsafeTestingDatabase(): void
    {
        $appEnv = (string) ($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? '');
        $dbConnection = (string) ($_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?? '');
        $dbDatabase = (string) ($_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?? '');

        if ($appEnv !== 'testing') {
            throw new RuntimeException('Unsafe test environment: APP_ENV must be "testing".');
        }

        $isSafeSqlite = $dbConnection === 'sqlite' && $dbDatabase === ':memory:';
        $isSafeMysqlTestDb = $dbConnection === 'mysql' && str_ends_with($dbDatabase, '_test');

        if ($isSafeSqlite || $isSafeMysqlTestDb) {
            return;
        }

        throw new RuntimeException(
            sprintf(
                'Unsafe test database: expected sqlite/:memory: or mysql/*_test, got %s/%s.',
                $dbConnection !== '' ? $dbConnection : '(empty)',
                $dbDatabase !== '' ? $dbDatabase : '(empty)'
            )
        );
    }
}
