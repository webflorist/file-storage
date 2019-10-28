<?php

namespace FileStorageTests\Feature;

use FileStorageTests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Webflorist\FileStorage\Models\StoredFile;
use Webflorist\FileStorage\Utilities\StoredFileAttributeMutator;

class StoredFileAttributeMutatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_null_to_uploaded_file()
    {
        $mutatedValue = StoredFileAttributeMutator::mutateFileAttribute(
            UploadedFile::fake()->image('new-file.pdf'),
            null,
            'test'
        );
        $this->assertIsUuid($mutatedValue);
        $this->assertDatabaseHas('stored_files',[
            'uuid' => $mutatedValue
        ]);
    }

    public function test_stored_file_to_null_with_delete()
    {
        $existingFile = $this->storeTestImage('test');
        $mutatedValue = StoredFileAttributeMutator::mutateFileAttribute(
            null,
            $existingFile->uuid,
            'test',
            true
        );
        $this->assertNull($mutatedValue);
        $this->assertDatabaseMissing('stored_files',[
            'uuid' => $existingFile->uuid
        ]);
        $this->assertFileMissingInStorage($existingFile->getPathname());
        $this->assertFileMissingInStorage($existingFile->getThumbnailPathname());
    }

    public function test_stored_file_to_null_without_delete()
    {
        $existingFile = $this->storeTestImage('test');
        $mutatedValue = StoredFileAttributeMutator::mutateFileAttribute(
            null,
            $existingFile->uuid,
            'test'
        );
        $this->assertNull($mutatedValue);
        $this->assertDatabaseHas('stored_files',[
            'uuid' => $existingFile->uuid
        ]);
        $this->assertFileExistsInStorage($existingFile->getPathname());
        $this->assertFileExistsInStorage($existingFile->getThumbnailPathname());
    }

    public function test_stored_file_to_uploaded_file_with_delete()
    {
        $existingFile = $this->storeTestImage('test');
        $newFile = UploadedFile::fake()->image('new-file.pdf');
        $mutatedValue = StoredFileAttributeMutator::mutateFileAttribute(
            $newFile,
            $existingFile->uuid,
            'test',
            true
        );
        $this->assertIsUuid($mutatedValue);
        $this->assertNotEquals($mutatedValue, $existingFile->uuid);
        $this->assertDatabaseMissing('stored_files',[
            'uuid' => $existingFile->uuid
        ]);
        $this->assertFileMissingInStorage($existingFile->getPathname());
        $this->assertFileMissingInStorage($existingFile->getThumbnailPathname());
    }

    public function test_stored_file_to_uploaded_file_with_failed_callback()
    {
        $existingFile = $this->storeTestImage('test');
        $newFile = UploadedFile::fake()->image('new-file.pdf');
        $mutatedValue = StoredFileAttributeMutator::mutateFileAttribute(
            $newFile,
            $existingFile->uuid,
            'test',
            true,
            function (UploadedFile $uploadedFile) {
                return false;
            }
        );
        $this->assertIsUuid($mutatedValue);
        $this->assertEquals($mutatedValue, $existingFile->uuid);
        $this->assertEquals(
            1,
            StoredFile::count()
        );
        $this->assertFileExistsInStorage($existingFile->getPathname());
        $this->assertFileExistsInStorage($existingFile->getThumbnailPathname());
    }

    public function test_stored_file_to_stored_file_with_delete()
    {
        $existingFile = $this->storeTestImage('test');
        $newFile = $this->storeTestImage('test');
        $mutatedValue = StoredFileAttributeMutator::mutateFileAttribute(
            ['stored_file_uuid' => $newFile->uuid],
            $existingFile->uuid,
            'test',
            true
        );
        $this->assertIsUuid($mutatedValue);
        $this->assertEquals($mutatedValue, $newFile->uuid);
        $this->assertDatabaseMissing('stored_files',[
            'uuid' => $existingFile->uuid
        ]);
        $this->assertEquals(
            1,
            StoredFile::count()
        );
        $this->assertFileExistsInStorage($newFile->getPathname());
        $this->assertFileExistsInStorage($newFile->getThumbnailPathname());
        $this->assertFileMissingInStorage($existingFile->getPathname());
        $this->assertFileMissingInStorage($existingFile->getThumbnailPathname());
    }

    public function test_null_to_uploaded_file_array()
    {
        $mutatedValue = StoredFileAttributeMutator::mutateFileArrayAttribute(
            [
                UploadedFile::fake()->image('new-file.pdf'),
                UploadedFile::fake()->image('new-file.pdf')
            ],
            null,
            'test'
        );
        $mutatedValue = json_decode($mutatedValue, true);
        $this->assertIsArray($mutatedValue);
        $this->assertEquals(
            2,
            count($mutatedValue)
        );
        foreach ($mutatedValue as $item) {
            $this->assertIsUuid($item);
            $this->assertDatabaseHas('stored_files',[
                'uuid' => $item
            ]);
        }
    }

    public function test_null_to_uploaded_file_array_with_one_failed_callback()
    {
        $mutatedValue = StoredFileAttributeMutator::mutateFileArrayAttribute(
            [
                UploadedFile::fake()->image('i-want-you.pdf'),
                UploadedFile::fake()->image('i-do-not-want-you.pdf')
            ],
            null,
            'test',
            true,
            function(UploadedFile $uploadedFile) {
                return $uploadedFile->getClientOriginalName() !== 'i-do-not-want-you.pdf';
            }
        );
        $mutatedValue = json_decode($mutatedValue, true);
        $this->assertIsArray($mutatedValue);
        $this->assertEquals(
            1,
            count($mutatedValue)
        );
        $this->assertIsUuid($mutatedValue[0]);
        $this->assertDatabaseHas('stored_files',[
            'uuid' => $mutatedValue[0],
            'path' => 'test',
            'name' => 'i-want-you.pdf'
        ]);
    }



    public function test_stored_file_array_to_mixed_file_array_with_failed_callback()
    {
        $existingFiles = [
            $this->storeTestFile('keep-me.pdf'),
            $this->storeTestFile('do-not-keep-me.pdf'),
            $this->storeTestFile('keep-me-too.pdf')
        ];
        $existingAttributeValue = [];
        foreach ($existingFiles as $storedFile) {
            $existingAttributeValue[] = $storedFile->uuid;
        }
        $newAttributeValue = [
            ['stored_file_uuid' => $existingFiles[0]->uuid],
            ['stored_file_uuid' => $existingFiles[2]->uuid],
            UploadedFile::fake()->image('new-file.pdf'),
            UploadedFile::fake()->image('new-file-we-do-not-want.exe')
        ];

        $mutatedValue = StoredFileAttributeMutator::mutateFileArrayAttribute(
            $newAttributeValue,
            $existingAttributeValue,
            'test',
            true,
            function (UploadedFile $uploadedFile) {
                return $uploadedFile->getClientMimeType() === 'application/pdf';
            }
        );
        $mutatedValue = json_decode($mutatedValue, true);
        $this->assertIsArray($mutatedValue);
        $this->assertEquals(
            3,
            count($mutatedValue)
        );
        $this->assertEquals(
            3,
            StoredFile::count()
        );
        $this->assertDatabaseHas('stored_files',[
            'uuid' => $existingFiles[0]->uuid
        ]);
        $this->assertDatabaseHas('stored_files',[
            'uuid' => $existingFiles[2]->uuid
        ]);
        $this->assertDatabaseHas('stored_files',[
            'name' => 'new-file.pdf'
        ]);
    }

}