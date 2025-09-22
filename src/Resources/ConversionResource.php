<?php

declare(strict_types=1);

namespace ConvertHub\Resources;

use ConvertHub\ConvertHubClient;
use ConvertHub\Exceptions\ApiException;
use ConvertHub\Exceptions\ValidationException;
use ConvertHub\Models\ConversionJob;
use ConvertHub\Models\ConversionOptions;

/**
 * Resource for handling file conversion operations
 */
class ConversionResource
{
    /**
     * @var ConvertHubClient
     */
    protected ConvertHubClient $client;

    /**
     * Create a new conversion resource instance
     *
     * @param ConvertHubClient $client
     */
    public function __construct(ConvertHubClient $client)
    {
        $this->client = $client;
    }

    /**
     * Convert a file to another format
     *
     * @param string $filePath Path to the file to convert
     * @param string $targetFormat Target format for conversion
     * @param array $options Optional conversion options
     * @return ConversionJob
     * @throws ApiException
     * @throws ValidationException
     */
    public function convert(string $filePath, string $targetFormat, array $options = []): ConversionJob
    {
        if (! file_exists($filePath)) {
            throw new ValidationException("File not found: {$filePath}");
        }

        $fileSize = filesize($filePath);
        $maxDirectUploadSize = 50 * 1024 * 1024; // 50MB

        if ($fileSize > $maxDirectUploadSize) {
            throw new ValidationException(
                "File size exceeds 50MB limit for direct upload. Please use chunked upload for large files."
            );
        }

        $multipart = [
            [
                'name' => 'file',
                'contents' => fopen($filePath, 'r'),
                'filename' => basename($filePath),
            ],
            [
                'name' => 'target_format',
                'contents' => $targetFormat,
            ],
        ];

        // Add optional parameters
        if (isset($options['output_filename'])) {
            $multipart[] = [
                'name' => 'output_filename',
                'contents' => $options['output_filename'],
            ];
        }

        if (isset($options['webhook_url'])) {
            $multipart[] = [
                'name' => 'webhook_url',
                'contents' => $options['webhook_url'],
            ];
        }

        // Add conversion options
        if (isset($options['options'])) {
            foreach ($options['options'] as $key => $value) {
                $multipart[] = [
                    'name' => "options[{$key}]",
                    'contents' => (string) $value,
                ];
            }
        }

        // Add metadata
        if (isset($options['metadata'])) {
            foreach ($options['metadata'] as $key => $value) {
                $multipart[] = [
                    'name' => "metadata[{$key}]",
                    'contents' => (string) $value,
                ];
            }
        }

        $response = $this->client->request('POST', 'convert', [
            'multipart' => $multipart,
        ]);

        return new ConversionJob($response);
    }

    /**
     * Convert a file from URL
     *
     * @param string $fileUrl URL of the file to convert
     * @param string $targetFormat Target format for conversion
     * @param array $options Optional conversion options
     * @return ConversionJob
     * @throws ApiException
     */
    public function convertFromUrl(string $fileUrl, string $targetFormat, array $options = []): ConversionJob
    {
        $payload = array_merge($options, [
            'file_url' => $fileUrl,
            'target_format' => $targetFormat,
        ]);

        $response = $this->client->request('POST', 'convert-url', [
            'json' => $payload,
        ]);

        return new ConversionJob($response);
    }

    /**
     * Create a conversion options builder
     *
     * @return ConversionOptions
     */
    public function options(): ConversionOptions
    {
        return new ConversionOptions();
    }
}
