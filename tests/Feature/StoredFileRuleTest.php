<?php

namespace FileStorageTests\Feature;

use FileStorageTests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webflorist\FileStorage\Rules\StoredFileRule;

class StoredFileRuleTest extends TestCase
{
    use RefreshDatabase;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['router']->middleware('web')
            ->post('test_stored_file_rule', function (Request $request) {
                $request->validate([
                    'my_already_stored_file' => [new StoredFileRule()],
                    'my_newly_uploaded_file' => [new StoredFileRule([
                        'mimes:pdf'
                    ])],
                ]);
                return response()->json('success');
            });
    }

    public function test_stored_file_rule_with_stored_file()
    {
        $storedFile = $this->storeTestFile('Test File.pdf', 'my/desired/file/path');
        $response = $this->post('test_stored_file_rule',[
            'my_already_stored_file' => [
                'stored_file_uuid' => $storedFile->uuid
            ]
        ]);
        $response->assertSessionHasNoErrors();
    }

    public function test_stored_file_rule_with_newly_uploaded_file()
    {
        $response = $this->post('test_stored_file_rule',[
            'my_newly_uploaded_file' => UploadedFile::fake()->image('new-file.pdf')
        ]);
        $response->assertSessionHasNoErrors();
    }

    public function test_stored_file_rule_with_newly_uploaded_file_with_wrong_mime_type()
    {
        $response = $this->post('test_stored_file_rule',[
            'my_newly_uploaded_file' => UploadedFile::fake()->image('new-file.jpg')
        ]);
        $response->assertSessionHasErrors(['my_newly_uploaded_file' => 'The my newly uploaded file must be a file of type: pdf.']);
    }

}