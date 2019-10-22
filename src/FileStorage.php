<?php

namespace Webflorist\FileStorage;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webflorist\FileStorage\Models\StoredFile;

/**
 * The main service-class of this package.
 *
 * Class FileStorageService
 * @package Webflorist\FileStorage
 *
 */
class FileStorage
{

    /**
     * Retrieves a file.
     *
     * @param string $uuid
     * @return StoredFile
     */
    public function get(string $uuid): StoredFile
    {
        return StoredFile::where('uuid', $uuid)->firstOrFail();
    }

    /**
     * Stores a file.
     *
     * @param UploadedFile $file
     * @param string $path
     * @param string|null $title
     * @return StoredFile
     */
    public function store(UploadedFile $file, string $path, string $title = null): StoredFile
    {
        $fileName = !is_null($title) ? $title . '.' . $file->getClientOriginalExtension() : $file->getClientOriginalName();
        $fileName = self::sanitizeFileName($fileName);

        $file->storeAs($path, $fileName);

        return StoredFile::create([
            'uuid' => self::generateUuid(),
            'name' => self::sanitizeFileName($fileName),
            'path' => $path,
            'title' => $title
        ]);

    }

    /**
     * Updates a file.
     *
     * @param string $uuid
     * @param array $data
     */
    public function update(string $uuid, array $data)
    {
        StoredFile::where('uuid', $uuid)->firstOrFail()->update($data);
    }

    /**
     * Deletes a file.
     *
     * @param string $uuid
     * @throws \Exception
     */
    public function delete(string $uuid)
    {

        // Retrieve file-info to delete.
        /** @var StoredFile $file2Delete */
        $file2Delete = StoredFile::where('uuid', $uuid)->firstOrFail();

        // Delete the file.
        Storage::delete($file2Delete->getPathname());

        // Delete the DB-entry.
        $file2Delete->delete();

    }

    private static function sanitizeFileName(string $fileName): string
    {
        return \URLify::filter($fileName, 60, "", true);
    }

    /**
     * Generate a unique UUID for a file
     *
     * @return string
     */
    private static function generateUuid() : string
    {
        $uuid = Str::uuid()->toString();

        // Check for uniqueness.
        if (StoredFile::where('uuid', $uuid)->exists()) {
            $uuid = self::generateUuid();
        }

        return $uuid;
    }

}