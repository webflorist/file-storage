<?php

namespace FileStorageTests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Intervention\Image\ImageServiceProvider;
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
            FileStorageServiceProvider::class,
            ImageServiceProvider::class
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'FileStorage' => FileStorageFacade::class,
            'Image' => Image::class
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
    protected function storeTestFile(string $fileName = 'test.pdf', string $filePath='test'): StoredFile
    {
        $storedFile = file_storage()->store(
            UploadedFile::fake()->create($fileName),
            $filePath
        );
        return $storedFile;
    }

    /**
     * @param string $filePath
     * @return StoredFile
     */
    protected function storeTestImage(string $filePath): StoredFile
    {
        $storedFile = file_storage()->store(
            new UploadedFile(__DIR__.'/files/flower.png', 'flower.png'),
            $filePath
        );
        return $storedFile;
    }

    protected function assertIsUuid(string $value)
    {
        if (strlen($value) !== 36) {
            return false;
        }
        if (substr_count($value,'-') !== 4) {
            return false;
        }
        return true;
    }

    /**
     * @param string $filePath
     */
    protected function assertFileExistsInStorage(string $filePath): void
    {
        Storage::assertExists($filePath);
    }

    /**
     * @param string $filePath
     */
    protected function assertFileMissingInStorage(string $filePath): void
    {
        Storage::assertMissing($filePath);
    }


}