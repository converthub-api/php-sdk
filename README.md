# ConvertHub PHP SDK

[![Latest Version](https://img.shields.io/packagist/v/converthub/php-sdk.svg)](https://packagist.org/packages/converthub/php-sdk)
[![PHP Version](https://img.shields.io/packagist/php-v/converthub/php-sdk.svg)](https://packagist.org/packages/converthub/php-sdk)
[![License](https://img.shields.io/packagist/l/converthub/php-sdk.svg)](https://github.com/converthub-api/php-sdk/blob/main/LICENSE)

Official PHP SDK for [ConvertHub API v2](https://converthub.com/api) - Convert files between 800+ format pairs with a simple, powerful API.

## Features

- **800+ Format Conversions** - Support for images, documents, audio, video, and more
- **Asynchronous Processing** - Non-blocking conversion with job-based workflow
- **Chunked Uploads** - Handle large files (up to 2GB)
- **Smart Caching** - Automatic caching for repeated conversions
- **Secure** - Industry-standard encryption and authentication
- **Developer-Friendly** - Clean API with detailed error handling

## Requirements

- PHP 8.0 or higher
- Composer
- ConvertHub API key ([Sign up here](https://converthub.com/api/signup))

## Installation

Install via Composer:

```bash
composer require converthub/php-sdk
```

## Quick Start

```php
<?php
require 'vendor/autoload.php';

use ConvertHub\ConvertHubClient;

// Initialize the client
$client = new ConvertHubClient('YOUR_API_KEY');

// Convert a file
$job = $client->conversions()->convert(
    'path/to/document.pdf',
    'docx'
);

// Wait for completion
$completedJob = $client->jobs()->waitForCompletion($job->getJobId());

// Get download URL
if ($completedJob->isCompleted()) {
    echo "Download URL: " . $completedJob->getDownloadUrl();
}
```

## Table of Contents

- [Authentication](#authentication)
- [Basic Usage](#basic-usage)
  - [Convert a File](#convert-a-file)
  - [Convert from URL](#convert-from-url)
  - [Check Job Status](#check-job-status)
  - [Download Converted File](#download-converted-file)
- [Advanced Features](#advanced-features)
  - [Conversion Options](#conversion-options)
  - [Chunked Upload](#chunked-upload-for-large-files)
  - [Webhooks](#webhooks)
  - [Metadata](#metadata)
- [Format Discovery](#format-discovery)
- [Error Handling](#error-handling)
- [Examples](#examples)

## Authentication

Get your API key from the [ConvertHub Dashboard](https://converthub.com/developers):

```php
$client = new ConvertHubClient('YOUR_API_KEY');
```

### Configuration Options

```php
$client = new ConvertHubClient('YOUR_API_KEY', [
    'base_url' => 'https://api.converthub.com', // Custom API endpoint
    'timeout' => 30,                             // Request timeout in seconds
    'connect_timeout' => 10,                     // Connection timeout
    'retry_enabled' => true,                     // Enable automatic retries
    'max_retries' => 3,                         // Maximum retry attempts
]);
```

## Basic Usage

### Convert a File

```php
// Simple conversion
$job = $client->conversions()->convert(
    'path/to/image.png',
    'jpg'
);

echo "Job ID: " . $job->getJobId() . "\n";
echo "Status: " . $job->getStatus() . "\n";
```

### Convert from URL

```php
// Convert a file from URL
$job = $client->conversions()->convertFromUrl(
    'https://example.com/document.pdf',
    'docx'
);

// Poll for completion
while ($job->isProcessing()) {
    sleep(2);
    $job = $client->jobs()->getStatus($job->getJobId());
}

if ($job->isCompleted()) {
    echo "Download: " . $job->getDownloadUrl() . "\n";
} else {
    echo "Error: " . $job->getErrorMessage() . "\n";
}
```

### Check Job Status

```php
$job = $client->jobs()->getStatus('job_123e4567-e89b-12d3-a456-426614174000');

if ($job->isCompleted()) {
    echo "Conversion completed!\n";
    echo "Download URL: " . $job->getDownloadUrl() . "\n";
    echo "File size: " . $job->getFileSize() . " bytes\n";
    echo "Expires at: " . $job->getExpiresAt() . "\n";
} elseif ($job->isFailed()) {
    echo "Conversion failed: " . $job->getErrorMessage() . "\n";
} else {
    echo "Still processing...\n";
}
```

### Download Converted File

```php
// Get download info
$downloadInfo = $client->jobs()->getDownloadUrl($job->getJobId());

echo "Filename: " . $downloadInfo->getFilename() . "\n";
echo "Size: " . $downloadInfo->getFileSizeFormatted() . "\n";

// Download to local file
$success = $downloadInfo->downloadTo('path/to/save/converted.docx');

if ($success) {
    echo "File downloaded successfully!\n";
}
```

## Advanced Features

### Conversion Options

```php
// Using the options builder
$options = $client->conversions()->options()
    ->setQuality(90)                        // Image quality (1-100)
    ->setResolution('1920x1080')           // Resolution for images/videos
    ->setBitrate('320k')                   // Audio/video bitrate
    ->setSampleRate(44100)                 // Audio sample rate
    ->setOutputFilename('my-document.pdf') // Custom output filename
    ->toArray();

$job = $client->conversions()->convert(
    'path/to/file.jpg',
    'pdf',
    $options
);

// Or pass options directly
$job = $client->conversions()->convert(
    'path/to/audio.wav',
    'mp3',
    [
        'options' => [
            'bitrate' => '320k',
            'sample_rate' => 48000
        ],
        'output_filename' => 'high-quality.mp3'
    ]
);
```

### Chunked Upload for Large Files

```php
// For files over 50MB, use chunked upload
$largeFile = 'path/to/large-video.mov';
$targetFormat = 'mp4';

// Automatic chunked upload with progress tracking
$job = $client->chunkedUpload()->uploadLargeFile(
    $largeFile,
    $targetFormat,
    [
        'webhook_url' => 'https://your-app.com/webhook',
        'metadata' => ['video_id' => '12345']
    ],
    null, // Use default chunk size
    function ($currentChunk, $totalChunks, $percentage) {
        echo "Progress: {$currentChunk}/{$totalChunks} ({$percentage}%)\n";
    }
);

echo "Conversion started: " . $job->getJobId() . "\n";
```

#### Manual Chunked Upload

```php
// For more control over the chunked upload process
$fileSize = filesize('large-file.mov');
$chunkSize = 10 * 1024 * 1024; // 10MB chunks
$totalChunks = ceil($fileSize / $chunkSize);

// 1. Initialize session
$session = $client->chunkedUpload()->initSession(
    'large-file.mov',
    $fileSize,
    $totalChunks,
    'mp4'
);

// 2. Upload chunks
$handle = fopen('large-file.mov', 'rb');
for ($i = 0; $i < $totalChunks; $i++) {
    $chunk = fread($handle, $chunkSize);
    $client->chunkedUpload()->uploadChunk(
        $session->getSessionId(),
        $i,
        $chunk
    );
    echo "Uploaded chunk " . ($i + 1) . " of {$totalChunks}\n";
}
fclose($handle);

// 3. Complete upload and start conversion
$job = $client->chunkedUpload()->complete($session->getSessionId());
```

### Webhooks

```php
// Set up webhook for async notification
$job = $client->conversions()->convert(
    'document.pdf',
    'docx',
    [
        'webhook_url' => 'https://your-app.com/webhooks/conversion-complete'
    ]
);

// In your webhook handler:
$payload = json_decode(file_get_contents('php://input'), true);

if ($payload['status'] === 'completed') {
    $downloadUrl = $payload['result']['download_url'];
    // Process the converted file
} else {
    $error = $payload['error']['message'];
    // Handle error
}
```

### Metadata

```php
// Add custom metadata for tracking
$job = $client->conversions()->convert(
    'invoice.pdf',
    'png',
    [
        'metadata' => [
            'user_id' => 'user_123',
            'invoice_id' => 'inv_456',
            'department' => 'accounting'
        ]
    ]
);

// Retrieve metadata from completed job
$completedJob = $client->jobs()->getStatus($job->getJobId());
$metadata = $completedJob->getMetadata();
echo "Invoice ID: " . $metadata['invoice_id'] . "\n";
```

## Format Discovery

### List All Supported Formats

```php
$formats = $client->formats()->all();

echo "Total formats: " . $formats['total_formats'] . "\n";

foreach ($formats['formats'] as $type => $formatList) {
    echo "\n{$type}:\n";
    foreach ($formatList as $format) {
        echo "  - {$format['extension']} ({$format['mime_type']})\n";
    }
}
```

### Check Format Support

```php
// Check if a format is supported
if ($client->formats()->isSupported('heic')) {
    echo "HEIC format is supported\n";
}

// Check if a specific conversion is supported
if ($client->formats()->isConversionSupported('pdf', 'docx')) {
    echo "PDF to DOCX conversion is available\n";
}

// Get all possible conversions for a format
$conversions = $client->formats()->getConversions('png');
echo "PNG can be converted to:\n";
foreach ($conversions['available_conversions'] as $conversion) {
    echo "  - {$conversion['target_format']}\n";
}
```

## Error Handling

```php
use ConvertHub\Exceptions\ValidationException;
use ConvertHub\Exceptions\AuthenticationException;
use ConvertHub\Exceptions\ApiException;

try {
    $job = $client->conversions()->convert('file.pdf', 'docx');
} catch (ValidationException $e) {
    // Handle validation errors
    echo "Validation Error: " . $e->getMessage() . "\n";
    
    if ($e->getValidationErrors()) {
        foreach ($e->getValidationErrors() as $field => $errors) {
            echo "  {$field}: " . implode(', ', $errors) . "\n";
        }
    }
} catch (AuthenticationException $e) {
    // Handle authentication errors
    echo "Authentication Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getErrorCode() . "\n";
} catch (ApiException $e) {
    // Handle general API errors
    echo "API Error: " . $e->getMessage() . "\n";
    
    if ($e->getCode() === 429) {
        // Rate limiting
        $retryAfter = $e->getErrorDetails()['retry_after'] ?? 60;
        echo "Rate limited. Retry after {$retryAfter} seconds\n";
    }
}
```

## Examples

### Complete Example: Document Processing Pipeline

```php
<?php
require 'vendor/autoload.php';

use ConvertHub\ConvertHubClient;
use ConvertHub\Exceptions\ApiException;

class DocumentProcessor
{
    private ConvertHubClient $client;
    
    public function __construct(string $apiKey)
    {
        $this->client = new ConvertHubClient($apiKey);
    }
    
    public function processDocument(string $inputFile, string $targetFormat): ?string
    {
        try {
            echo "Starting conversion of {$inputFile} to {$targetFormat}...\n";
            
            // Start conversion
            $job = $this->client->conversions()->convert(
                $inputFile,
                $targetFormat,
                [
                    'metadata' => [
                        'timestamp' => time(),
                        'source_file' => basename($inputFile)
                    ]
                ]
            );
            
            echo "Job created: {$job->getJobId()}\n";
            
            // Wait for completion with timeout
            $completedJob = $this->client->jobs()->waitForCompletion(
                $job->getJobId(),
                2,  // Poll every 2 seconds
                120 // Max wait 2 minutes
            );
            
            if ($completedJob->isCompleted()) {
                // Download the converted file
                $downloadInfo = $this->client->jobs()->getDownloadUrl(
                    $completedJob->getJobId()
                );
                
                $outputFile = 'output/' . $downloadInfo->getFilename();
                
                if ($downloadInfo->downloadTo($outputFile)) {
                    echo "✅ Conversion successful!\n";
                    echo "📁 Saved to: {$outputFile}\n";
                    echo "📏 Size: " . $downloadInfo->getFileSizeFormatted() . "\n";
                    
                    // Clean up - delete from server
                    $this->client->jobs()->delete($completedJob->getJobId());
                    
                    return $outputFile;
                }
            } else {
                echo "❌ Conversion failed: " . $completedJob->getErrorMessage() . "\n";
            }
            
        } catch (ApiException $e) {
            echo "❌ API Error: " . $e->getMessage() . "\n";
        } catch (\Exception $e) {
            echo "❌ Unexpected error: " . $e->getMessage() . "\n";
        }
        
        return null;
    }
}

// Usage
$processor = new DocumentProcessor('YOUR_API_KEY');
$processor->processDocument('documents/report.pdf', 'docx');
```

### Batch Processing Example

```php
// Convert multiple files in parallel
$files = [
    ['input' => 'file1.pdf', 'format' => 'docx'],
    ['input' => 'file2.png', 'format' => 'jpg'],
    ['input' => 'file3.mp3', 'format' => 'wav'],
];

$jobs = [];

// Start all conversions
foreach ($files as $file) {
    $job = $client->conversions()->convert(
        $file['input'],
        $file['format']
    );
    $jobs[] = [
        'job' => $job,
        'input' => $file['input'],
        'format' => $file['format']
    ];
    echo "Started conversion: {$file['input']} -> {$file['format']}\n";
}

// Wait for all to complete
foreach ($jobs as $jobInfo) {
    $completedJob = $client->jobs()->waitForCompletion(
        $jobInfo['job']->getJobId()
    );
    
    if ($completedJob->isCompleted()) {
        echo "✅ {$jobInfo['input']} converted successfully\n";
    } else {
        echo "❌ {$jobInfo['input']} failed: " . $completedJob->getErrorMessage() . "\n";
    }
}
```

## API Documentation

For complete API documentation, visit [ConvertHub API Docs](https://converthub.com/api/docs).

## Testing

Run the test suite:

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run static analysis
composer analyze

# Fix code style
composer format
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Support

- **Documentation**: [https://converthub.com/api/docs](https://converthub.com/api/docs)
- **Issues**: [GitHub Issues](https://github.com/converthub-api/php-sdk/issues)
- **Email**: support@converthub.com

## License

This SDK is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

Developed and maintained by [ConvertHub](https://converthub.com).

---

**Ready to start converting?** [Get your API key](https://converthub.com/api/signup) and begin transforming files in minutes!
