<?php

namespace Webflorist\FileStorage\Utilities;

use Illuminate\Http\UploadedFile;
use Webflorist\FileStorage\Exceptions\StoredFileNotFoundException;

class StoredFileAttributeMutator
{

    /**
     * Mutate the value for an attribute, that stores the UUID of a StoredFile.
     *
     * @param array|UploadedFile|null $newValue can be:
     *      - array containing an existing UUID under the key 'stored_file_uuid' (to keep or switch the file)
     *      - null (to remove file)
     *      - UploadedFile (to store a newly uploaded file)
     * @param null|string $existingValue: Either null or UUID of an existing StoredFile
     * @param string $storagePath
     * @param bool $deleteOldFile
     * @param callable|null $uploadedFileCallback
     * @param callable|null $storedFileCallback
     * @return string|null
     * @throws StoredFileNotFoundException
     */
    public static function mutateFileAttribute($newValue, ?string $existingValue, string $storagePath, bool $deleteOldFile = false, ?callable $uploadedFileCallback = null, ?callable $storedFileCallback = null)
    {

        // Per default we assume the new value should be null.
        $mutatedValue = null;

        // If a stored_file_uuid was submitted, we simply return it,
        // if $storedFileCallback doesn't return false.
        if (is_array($newValue) && isset($newValue['stored_file_uuid'])) {

            if (is_callable($storedFileCallback)) {
                $switchThisFile = call_user_func_array($storedFileCallback,[file_storage()->get($newValue['stored_file_uuid'])]);

                if ($switchThisFile === false) {
                    return $existingValue;
                }
            }

            $mutatedValue = $newValue['stored_file_uuid'];
        }

        // If a new file was uploaded, we store it and return it's UUID.
        if (is_object($newValue) && is_a($newValue, UploadedFile::class)) {

            if (is_callable($uploadedFileCallback)) {
                $storeThisFile = call_user_func_array($uploadedFileCallback,[$newValue]);

                // The callback can return false to stop processing of this file (e.g. due to an additional validation).
                // We simply return $existingValue then.
                if ($storeThisFile === false) {
                    return $existingValue;
                }
            }

            $mutatedValue = file_storage()->store(
                $newValue,
                $storagePath
            )->uuid;
        }

        // Perform deletion, if a change of files happened, and deletion was requested.
        if (!is_null($existingValue) && ($existingValue !== $mutatedValue) && $deleteOldFile) {
            file_storage()->delete($existingValue);
        }

        return $mutatedValue;
    }

    /**
     * Mutate the value for an attribute, that stores an array of UUIDs of StoredFiles.
     *
     * @param null|array $newValue See function mutateFileAttribute.
     * @param null|array $existingValue
     * @param string $storagePath
     * @param bool $deleteOldFiles
     * @param callable|null $uploadedFileCallback
     * @param callable|null $storedFileCallback
     * @return false|string|null
     * @throws StoredFileNotFoundException
     */
    public static function mutateFileArrayAttribute(?array $newValue, ?array$existingValue, string $storagePath, bool $deleteOldFiles = false, ?callable $uploadedFileCallback = null, ?callable $storedFileCallback = null)
    {
        $mutatedValue = [];
        if (is_array($newValue)) {
            foreach ($newValue as $valueItem) {
                $mutatedValueItem =  self::mutateFileAttribute($valueItem, null, $storagePath, false, $uploadedFileCallback, $storedFileCallback);
                if ($mutatedValueItem !== null) {
                    $mutatedValue[] = $mutatedValueItem;
                }
            }
        }

        // Delete old images
        if (is_array($existingValue) && $deleteOldFiles) {
            foreach ($existingValue as $storedFileUuid) {
                if (array_search($storedFileUuid, $mutatedValue) === false) {
                    file_storage()->delete($storedFileUuid);
                }
            }
        }
        return count($mutatedValue) > 0 ? json_encode($mutatedValue) : null;
    }
}
