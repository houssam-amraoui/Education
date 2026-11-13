<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $connection = $app['db']->connection();

        if ($connection->getDriverName() !== 'mysql' || $connection->getDatabaseName() !== 'laravel_test') {
            throw new RuntimeException('Feature tests require the dedicated MySQL database laravel_test. Clear cached configuration before testing.');
        }

        return $app;
    }
}
