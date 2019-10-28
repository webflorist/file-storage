<?php

namespace FileStorageTests\Feature;

use FileStorageTests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_file()
    {
        $fileName = 'Test File.pdf';
        $filePath = 'my/desired/file/path';
        $storedFile = $this->storeTestFile($fileName, $filePath);

        $this->assertEquals('test-file.pdf', $storedFile->name);
        $this->assertEquals($filePath, $storedFile->path);

        $this->assertFileExistsInStorage("$filePath/test-file.pdf");
        return $storedFile->uuid;
    }

    public function test_store_file_with_title()
    {
        $fileName = 'Test File.pdf';
        $title = "My Desired File Title";
        $filePath = 'my/desired/file/path';
        $storedFile = file_storage()->store(
            UploadedFile::fake()->create($fileName),
            $filePath,
            $title
        );
        $this->assertEquals('my-desired-file-title.pdf', $storedFile->name);
        $this->assertEquals($filePath, $storedFile->path);

        $this->assertFileExistsInStorage("$filePath/my-desired-file-title.pdf");
        return $storedFile->uuid;
    }

    public function test_update_title()
    {
        $newTitle = 'My New File Title';
        $uuid = $this->test_store_file();
        file_storage()->update($uuid, [
            'title' => $newTitle
        ]);
        $this->assertEquals(
            $newTitle,
            file_storage()->get($uuid)->title
        );
    }

    public function test_delete_file()
    {
        $uuid = $this->test_store_file();
        file_storage()->delete($uuid);
        $this->assertFileMissingInStorage("my/desired/file/path/test-file.pdf");
    }

    public function test_unique_file_name()
    {
        $fileName = 'Test File.pdf';
        $filePath = 'my/desired/file/path';
        $this->storeTestFile($fileName, $filePath);
        $this->storeTestFile($fileName, $filePath);
        $this->assertFileExistsInStorage("$filePath/test-file_1.pdf");
    }

}