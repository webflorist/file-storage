<?php

use Webflorist\FileStorage\FileStorage;

if (!function_exists('file_storage')) {
    /**
     * Gets the FileStorag singleton from Laravel's service-container
     *
     * @return FileStorage
     */
    function file_storage()
    {
        return app(FileStorage::class);
    }
}