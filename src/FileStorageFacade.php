<?php

namespace Webflorist\FileStorage;

use Illuminate\Support\Facades\Facade;

class FileStorageFacade extends Facade
{

    /**
     * Static access-proxy for the FileStorage
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return FileStorage::class;
    }

}