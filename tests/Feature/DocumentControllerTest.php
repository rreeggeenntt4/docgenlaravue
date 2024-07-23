<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class DocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function genandup()
    {
        // Mocking storage
        Storage::fake('public');

        // Create a test request payload
        $data = [
            'title' => 'Test Document',
            'date' => '2024-07-23',
        ];

        // Mock time to ensure consistent filename
        $fixedTime = time();
        $this->travelTo(now()->setTimestamp($fixedTime));

        // Send POST request to the controller
        $response = $this->post('/generate', $data);

        // Assert that the response is a file download
        $response->assertStatus(200);

        // Get the content-disposition header
        $contentDisposition = $response->headers->get('content-disposition');

        // Check if the response headers contain 'content-disposition' header with expected filename
        $expectedFilename = 'attachment; filename=doc_' . $fixedTime . '.docx';
        $this->assertStringContainsString(
            $expectedFilename,
            $contentDisposition,
            'Content-Disposition header does not contain the expected filename. Actual header: ' . $contentDisposition
        );

        // Assert the file was stored in the fake storage
        $expectedFilePath = 'doc_' . $fixedTime . '.docx';
        $this->assertTrue(Storage::disk('public')->exists($expectedFilePath), 'The file does not exist in fake storage.');

        // Assert that exactly one file was created
        $files = Storage::disk('public')->allFiles();
        $this->assertCount(1, $files, 'No files were found in storage.');
    }
}
