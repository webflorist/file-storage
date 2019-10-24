<?php

namespace Webflorist\FileStorage;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webflorist\FileStorage\Exceptions\FileAlreadyExistsException;
use Webflorist\FileStorage\Exceptions\StoredFileNotFoundException;
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
        try {
            return StoredFile::where('uuid', $uuid)->firstOrFail();
        }
        catch(ModelNotFoundException $modelNotFoundException) {
            throw new StoredFileNotFoundException("No stored file found in datbase with UUID '$uuid'.");
        }
    }

    /**
     * Searches for files.
     *
     * @param string $uuid
     * @return Collection
     */
    public function search(array $attributes): Collection
    {
        return StoredFile::where($attributes)->get();
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

        if (Storage::exists($path.'/'.$fileName)) {
            throw new FileAlreadyExistsException("A file with name $fileName already exists in folder $path.");
        }

        $file->storeAs($path, $fileName);

        return StoredFile::create([
            'uuid' => self::generateUuid(),
            'name' => $fileName,
            'path' => $path,
            'title' => $title ?? pathinfo($file->getClientOriginalName(),PATHINFO_FILENAME)
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
        try {
            StoredFile::where('uuid', $uuid)->firstOrFail()->update($data);
        }
        catch(ModelNotFoundException $modelNotFoundException) {
            throw new StoredFileNotFoundException("No stored file found in datbase with UUID '$uuid'.");
        }
    }

    /**
     * Deletes a file.
     *
     * @param string $uuid
     * @throws \Exception
     */
    public function delete(string $uuid)
    {
        try {
            // Retrieve file-info to delete.
            /** @var StoredFile $file2Delete */
            $file2Delete = StoredFile::where('uuid', $uuid)->firstOrFail();

            // Delete the file.
            Storage::delete($file2Delete->getPathname());

            // Delete the DB-entry.
            $file2Delete->delete();
        }
        catch(ModelNotFoundException $modelNotFoundException) {
            throw new StoredFileNotFoundException("No stored file found in datbase with UUID '$uuid'.");
        }
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