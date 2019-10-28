<?php

namespace Webflorist\FileStorage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @property string uuid
 * @property string name
 * @property string path
 * @property string title
 */
class StoredFile extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'name',
        'path',
        'title',
    ];

    public function getTitleAttribute($value)
    {
        return $value ?? $this->getBasename();
    }

    public function getPathname()
    {
        return $this->path . '/' . $this->name;
    }

    public function getSize()
    {
        return Storage::size($this->getPathname());
    }

    public function getMimeType() {
        return Storage::mimeType($this->getPathname());
    }

    public function getExtension() {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }

    public function getBasename() {
        return pathinfo($this->name, PATHINFO_FILENAME);
    }

    public function getUrl() {
        return Storage::url($this->getPathname());
    }

    public function hasThumbnail() {
        Storage::exists($this->getThumbnailPathname());
    }

    public function getThumbnailPathname()
    {
        return $this->path . '/thumbs/' . $this->name;
    }

    public function getThumbnailUrl() {
        return Storage::url($this->getThumbnailPathname());
    }

}
