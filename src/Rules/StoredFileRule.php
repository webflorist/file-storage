<?php

namespace Webflorist\FileStorage\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Webflorist\FileStorage\Exceptions\StoredFileNotFoundException;

class StoredFileRule implements Rule
{
    /**
     * @var array|null
     */
    private $fileRules;

    private $errorMessage;

    /**
     * Create a new rule instance.
     *
     * @param array|null $fileRules: Rules you want to apply to a newly uploaded file.
     */
    public function __construct(array $fileRules=[])
    {
        if (array_search('file',$fileRules) === false) {
            $fileRules[] = 'file';
        }
        $this->fileRules = $fileRules;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (is_array($value) && isset($value['stored_file_uuid'])) {
            return $this->validateStoredFile($value);
        }
        if (is_object($value)) {
            return $this->validateUploadedFile($attribute, $value);
        }
        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->errorMessage;
    }

    /**
     * @param $value
     * @return bool
     */
    private function validateStoredFile($value): bool
    {
        try {
            file_storage()->get($value['stored_file_uuid']);
            return true;
        } catch (StoredFileNotFoundException $exception) {
            $this->errorMessage = __('Webflorist-FileStorage::validation.stored_file_not_found');
            return false;
        }
    }

    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    private function validateUploadedFile($attribute, $value): bool
    {
        $validator = Validator::make([$attribute => $value], [
            $attribute => $this->fileRules,
        ]);
        if ($validator->fails()) {
            $this->errorMessage = $validator->getMessageBag()->get($attribute);
            return false;
        } else {
            return true;
        }
    }
}
