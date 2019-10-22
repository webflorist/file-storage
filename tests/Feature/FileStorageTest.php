<?php

namespace FileStorageTests\Feature;

use FileStorageTests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_file()
    {
        $fileName = 'Test File.pdf';
        $filePath = 'my/desired/file/path';
        $storedFile = file_storage()->store(
            UploadedFile::fake()->create($fileName),
            $filePath
        );
        $this->assertEquals('test-file.pdf', $storedFile->name);
        $this->assertEquals($filePath, $storedFile->path);

        Storage::assertExists("$filePath/test-file.pdf");
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

        Storage::assertExists("$filePath/my-desired-file-title.pdf");
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
        Storage::assertMissing("my/desired/file/path/test-file.pdf");
    }

}