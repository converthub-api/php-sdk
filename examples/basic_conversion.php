<?php

/**
 * Basic Conversion Example
 *
 * This example demonstrates how to:
 * - Initialize the ConvertHub client
 * - Convert a file to another format
 * - Check conversion status
 * - Download the converted file
 */

require_once __DIR__ . '/../vendor/autoload.php';

use ConvertHub\ConvertHubClient;
use ConvertHub\Exceptions\ApiException;
use ConvertHub\Exceptions\AuthenticationException;
use ConvertHub\Exceptions\ValidationException;

// Get API key from environment or replace with your actual key
$apiKey = getenv('CONVERTHUB_API_KEY') ?: 'YOUR_API_KEY_HERE';

if ($apiKey === 'YOUR_API_KEY_HERE') {
    die("Please set your ConvertHub API key\n");
}

try {
    // Initialize client
    echo "Initializing ConvertHub client...\n";
    $client = new ConvertHubClient($apiKey);

    // Check API health
    $health = $client->health();
    echo "✅ API Status: {$health['status']}\n";
    echo "API Version: {$health['api_version']}\n";
    echo "\n";

    // Example 1: Convert a PDF to DOCX
    echo "Example 1: Converting PDF to DOCX\n";
    echo "================================\n";

    $pdfFile = __DIR__ . '/sample.pdf';

    if (! file_exists($pdfFile)) {
        die("❌ Sample PDF file not found at {$pdfFile}\n");
    }

    // Start conversion
    echo "Starting conversion...\n";
    $job = $client->conversions()->convert(
        $pdfFile,
        'docx',
        [
            'output_filename' => 'converted_document.docx',
            'metadata' => [
                'example' => 'basic_conversion',
                'timestamp' => date('Y-m-d H:i:s'),
            ],
        ]
    );

    echo "Job ID: {$job->getJobId()}\n";
    echo "Status: {$job->getStatus()}\n";

    // Wait for completion
    echo "Waiting for conversion to complete...\n";
    $completedJob = $client->jobs()->waitForCompletion(
        $job->getJobId(),
        2,  // Check every 2 seconds
        60  // Maximum 60 seconds
    );

    if ($completedJob->isCompleted()) {
        echo "✅ Conversion completed successfully!\n";
        echo "Download URL: {$completedJob->getDownloadUrl()}\n";
        echo "File size: " . number_format($completedJob->getFileSize() / 1024, 2) . " KB\n";

        // Download the file
        $downloadInfo = $client->jobs()->getDownloadUrl($completedJob->getJobId());
        $outputDir = __DIR__ . '/output';

        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $outputPath = $outputDir . '/' . $downloadInfo->getFilename();

        echo "Downloading to: {$outputPath}\n";
        if ($downloadInfo->downloadTo($outputPath)) {
            echo "✅ File downloaded successfully!\n";
        } else {
            echo "❌ Failed to download file\n";
        }

        // Clean up server file
        echo "Cleaning up...\n";
        $client->jobs()->delete($completedJob->getJobId());

    } elseif ($completedJob->isFailed()) {
        echo "❌ Conversion failed!\n";
        echo "Error: {$completedJob->getErrorMessage()}\n";
        echo "Error code: {$completedJob->getErrorCode()}\n";
    }

    echo "\n";

    // Example 2: Check format support
    echo "Example 2: Format Discovery\n";
    echo "==========================\n";

    // Check if PDF to DOCX conversion is supported
    if ($client->formats()->isConversionSupported('pdf', 'docx')) {
        echo "✅ PDF to DOCX conversion is supported\n";
    }

    // Get all conversions available for PNG
    $pngConversions = $client->formats()->getConversions('png');
    echo "\nPNG can be converted to {$pngConversions['total_conversions']} formats:\n";

    $formats = array_slice($pngConversions['available_conversions'], 0, 10);
    foreach ($formats as $format) {
        echo "  - {$format['target_format']}";
        if (isset($format['mime_type'])) {
            echo " ({$format['mime_type']})";
        }
        echo "\n";
    }

    if (count($pngConversions['available_conversions']) > 10) {
        echo "  ... and " . (count($pngConversions['available_conversions']) - 10) . " more\n";
    }

} catch (AuthenticationException $e) {
    echo "❌ Authentication Error: {$e->getMessage()}\n";
    echo "Please check your API key\n";
} catch (ValidationException $e) {
    echo "❌ Validation Error: {$e->getMessage()}\n";
    if ($e->getValidationErrors()) {
        echo "Validation errors:\n";
        foreach ($e->getValidationErrors() as $field => $errors) {
            echo "  - {$field}: " . implode(', ', $errors) . "\n";
        }
    }
} catch (ApiException $e) {
    echo "❌ API Error: {$e->getMessage()}\n";
    echo "Error code: {$e->getErrorCode()}\n";
    if ($e->getCode() === 429) {
        $details = $e->getErrorDetails();
        echo "Rate limited. Retry after {$details['retry_after']} seconds\n";
    }
} catch (\Exception $e) {
    echo "❌ Unexpected Error: {$e->getMessage()}\n";
}

echo "\nDone!\n";
