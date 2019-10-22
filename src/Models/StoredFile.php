<?php

namespace Webflorist\FileStorage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

    public function getPathname()
    {
        return $this->path . '/' . $this->name;
    }


    public function storeDocument(UploadedFile $uploadedFile, string $documentType)
    {
        if (array_search($documentType, Supplier::DOCUMENT_TYPES) === false) {
            throw new \Exception("Document of type '$documentType' not allowed.");
        }
        return $uploadedFile->storeAs($this->getDocumentFolder($documentType), $uploadedFile->getClientOriginalName());
    }

    public function deleteDocument(string $fileName, string $documentType)
    {
        Storage::delete($this->getDocumentFolder($documentType) . '/' . $fileName);
    }

    public function renameDocument(string $fileName, string $documentType, string $newFileName)
    {
        $folder = $this->getDocumentFolder($documentType) . '/';
        Storage::move($folder . $fileName, $folder . $newFileName);
    }

    public function getDocumentIndex()
    {
        $filesData = [];
        foreach (self::DOCUMENT_TYPES as $documentType) {
            $filesData[$documentType] = [];

            foreach (Storage::files($this->getDocumentFolder($documentType)) as $key => $filePath) {
                $fileName = explode('/', $filePath);
                $fileName = end($fileName);
                $filesData[$documentType][] = [
                    "name" => $fileName,
                    "size" => Storage::size($filePath), // 24 MB
                    "type" => Storage::mimeType($filePath),
                    "ext" => "pdf",
                    "url" => Storage::url($filePath),
                    "hash" => md5($filePath),
                ];
            }
        }
        return $filesData;
    }

    private static function mergeProductIndexes(array &$productIndex, array $nestedRootLineCategoriesWithProduct)
    {
        foreach ($nestedRootLineCategoriesWithProduct as $categoryId => $categoryData) {
            if (!isset($productIndex[$categoryId])) {
                $productIndex[$categoryId] = $categoryData;
            } else {
                if (isset($productIndex[$categoryId]['products'])) {
                    $productIndex[$categoryId]['products'] = array_merge($productIndex[$categoryId]['products'], $categoryData['products']);
                } else {
                    self::mergeProductIndexes($productIndex[$categoryId]['subcategories'], $categoryData['subcategories']);
                }
            }
        }
    }

    public function getHashedId()
    {
        return hash('crc32', $this->id, FALSE);;
    }

    private function getDocumentFolder(string $documentType)
    {
        $userSegment = $this->getHashedId();
        return "suppliers/$userSegment/$documentType";
    }

}
