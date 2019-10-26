<?php

namespace FileStorageTests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Router;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Webflorist\FileStorage\FileStorageFacade;
use Webflorist\FileStorage\FileStorageServiceProvider;
use Webflorist\FileStorage\Models\StoredFile;

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

    protected function tearDown() : void
    {
        (new Filesystem())->deleteDirectories(
            storage_path('app')
        );
        parent::tearDown();
    }

    /**
     * @param string $fileName
     * @param string $filePath
     * @return StoredFile
     */
    protected function storeTestFile(string $fileName, string $filePath): StoredFile
    {
        $storedFile = file_storage()->store(
            UploadedFile::fake()->create($fileName),
            $filePath
        );
        return $storedFile;
    }


}