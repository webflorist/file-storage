<?php

namespace FileStorageTests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Routing\Router;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Webflorist\FileStorage\FileStorageFacade;
use Webflorist\FileStorage\FileStorageServiceProvider;

/**
 * Class TestCase
 * @package FileStorageTests
 */
class TestCase extends BaseTestCase
{
    /**
     * @var Repository
     */
    protected $config;
    /**
     * @var Router
     */
    protected $router;

    protected function getPackageProviders($app)
    {
        return [
            FileStorageServiceProvider::class
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'FileStorage' => FileStorageFacade::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $this->router = $app[Router::class];
        $this->config = $app['config'];
    }


}