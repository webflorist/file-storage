<?php

namespace Webflorist\FileStorage;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Constraint;
use Intervention\Image\Facades\Image;
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
     * @throws StoredFileNotFoundException
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
     * @param array $attributes
     * @return Collection
     */
    public function search(array $attributes): Collection
    {
        return StoredFile::where($attributes)->get();
    }

    /**
     * Stores a file.
     *
     * @param UploadedFile|string $file
     * @param string $path
     * @param string|null $title
     * @return StoredFile
     */
    public function store($file, string $path, ?string $title = null, bool $storeThumbnail = true ): StoredFile
    {
        // Establish filename.
        $fileName = !is_null($title) ? $title . '.' . $this->getFileExtension($file) : $this->getOriginalFileName($file);
        $fileName = self::sanitizeFileName($fileName);
        $fileName = self::makeFileNameUnique($path, $fileName);

        if ($storeThumbnail && $this->fileIsImage($file)) {
            $this->storeThumbnail($file, $path, $fileName);
        }

        $this->storeFile($file, $path, $fileName);

        return StoredFile::create([
            'uuid' => self::generateUuid(),
            'name' => $fileName,
            'path' => $path,
            'title' => $title ?? pathinfo($this->getOriginalFileName($file),PATHINFO_FILENAME)
        ]);

    }

    /**
     * Updates a file.
     *
     * @param string $uuid
     * @param array $data
     * @throws StoredFileNotFoundException
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

            // Delete the thumbnail.
            if ($file2Delete->hasThumbnail()) {
                Storage::delete($file2Delete->getThumbnailPathname());
            }

            // Delete the DB-entry.
            $file2Delete->delete();
        }
        catch(ModelNotFoundException $modelNotFoundException) {
            throw new StoredFileNotFoundException("No stored file found in datbase with UUID '$uuid'.");
        }
    }

    public static function sanitizeFileName(string $fileName): string
    {
        return \URLify::filter($fileName, 60, "", true);
    }

    /**
     * @param string $path
     * @param string $fileName
     * @param int|null $numberSuffix
     * @return string
     */
    private static function makeFileNameUnique(string $path, string $fileName, int $numberSuffix=null): string
    {
        $uniqueFileName = $fileName;
        if (!is_null($numberSuffix)) {
            if (strpos($fileName,'.') !== false) {
                $explodedFileName = explode('.',$fileName);
                $explodedFileName[count($explodedFileName)-2] .= "_$numberSuffix";
                $uniqueFileName = implode('.',$explodedFileName);
            }
            else {
                $uniqueFileName .= "_$numberSuffix";
            }
        }
        if (Storage::exists($path . '/' . $uniqueFileName)) {
            $nextNumberSuffixToTry = (is_null($numberSuffix)) ? 1 : $numberSuffix+1;
            return self::makeFileNameUnique($path, $fileName, $nextNumberSuffixToTry);
        }
        return $uniqueFileName;
    }

    /**
     * Generate a unique UUID for a file
     *
     * @return string
     */
    public static function generateUuid() : string
    {
        $uuid = Str::uuid()->toString();

        // Check for uniqueness.
        if (StoredFile::where('uuid', $uuid)->exists()) {
            $uuid = self::generateUuid();
        }

        return $uuid;
    }

    /**
     * @param UploadedFile $file
     * @param string $path
     * @param string $fileName
     */
    private function storeThumbnail(UploadedFile $file, string $path, string $fileName): void
    {
        $fileThumb = stream_get_meta_data(tmpfile())['uri'];
        $image = Image::make($file->path())->resize(300, null, function (Constraint $constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->save($fileThumb);
        Storage::putFileAs("$path/thumbs", new File($fileThumb), $fileName);
    }

    /**
     * @param $file
     * @return string|null
     */
    private function getFileExtension($file) : ?string
    {
        if (is_object($file) && is_a($file, UploadedFile::class)) {
            return $file->clientExtension();
        }

        if (is_string($file)) {
            $explodedFileName = explode('.',$this->getOriginalFileName($file));
            return end($explodedFileName);
        }

    }

    /**
     * @param $file
     * @return mixed
     */
    private function getOriginalFileName($file)
    {
        if (is_object($file) && is_a($file, UploadedFile::class)) {
            return $file->getClientOriginalName();
        }

        if (is_string($file)) {
            $explodedFile = explode('/',$file);
            return end($explodedFile);
        }
    }

    /**
     * @param $file
     * @return bool
     */
    private function fileIsImage($file): bool
    {
        return array_search($this->getFileExtension($file), ['jpg', 'jpeg', 'png', 'gif']) !== false;
    }

    /**
     * @param $file
     * @param string $path
     * @param string $fileName
     */
    private function storeFile($file, string $path, string $fileName): void
    {
        if (is_object($file) && is_a($file, UploadedFile::class)) {
            $file->storeAs($path, $fileName);
        }

        if (is_string($file)) {
            Storage::move($file, "$path/$fileName");
        }
    }

}
