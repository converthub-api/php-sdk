<?php

declare(strict_types=1);

namespace ConvertHub\Resources;

use ConvertHub\ConvertHubClient;
use ConvertHub\Exceptions\ApiException;
use ConvertHub\Exceptions\ValidationException;
use ConvertHub\Models\ChunkedUploadSession;
use ConvertHub\Models\ConversionJob;

/**
 * Resource for handling chunked upload operations for large files
 */
class ChunkedUploadResource
{
    /**
     * @var ConvertHubClient
     */
    protected ConvertHubClient $client;

    /**
     * Default chunk size (5MB)
     */
    public const DEFAULT_CHUNK_SIZE = 5 * 1024 * 1024;

    /**
     * Create a new chunked upload resource instance
     *
     * @param ConvertHubClient $client
     */
    public function __construct(ConvertHubClient $client)
    {
        $this->client = $client;
    }

    /**
     * Initialize a chunked upload session
     *
     * @param string $filename Original filename
     * @param int $fileSize Total file size in bytes
     * @param int $totalChunks Number of chunks
     * @param string $targetFormat Target format for conversion
     * @param array $options Optional parameters
     * @return ChunkedUploadSession
     * @throws ApiException
     */
    public function initSession(
        string $filename,
        int $fileSize,
        int $totalChunks,
        string $targetFormat,
        array $options = []
    ): ChunkedUploadSession {
        $payload = array_merge($options, [
            'filename' => $filename,
            'file_size' => $fileSize,
            'total_chunks' => $totalChunks,
            'target_format' => $targetFormat,
        ]);

        $response = $this->client->request('POST', 'upload/init', [
            'json' => $payload,
        ]);

        return new ChunkedUploadSession($response);
    }

    /**
     * Upload a chunk
     *
     * @param string $sessionId Upload session ID
     * @param int $chunkIndex Chunk index (0-based)
     * @param string $chunkData Chunk data
     * @return array
     * @throws ApiException
     */
    public function uploadChunk(string $sessionId, int $chunkIndex, string $chunkData): array
    {
        return $this->client->request("POST", "upload/{$sessionId}/chunks/{$chunkIndex}", [
            'multipart' => [
                [
                    'name' => 'chunk',
                    'contents' => $chunkData,
                    'filename' => "chunk_{$chunkIndex}",
                ],
            ],
        ]);
    }

    /**
     * Complete chunked upload and start conversion
     *
     * @param string $sessionId Upload session ID
     * @return ConversionJob
     * @throws ApiException
     */
    public function complete(string $sessionId): ConversionJob
    {
        $response = $this->client->request('POST', "upload/{$sessionId}/complete");

        return new ConversionJob($response);
    }

    /**
     * Upload a large file using chunked upload
     *
     * @param string $filePath Path to the file to upload
     * @param string $targetFormat Target format for conversion
     * @param array $options Optional parameters
     * @param int|null $chunkSize Chunk size in bytes (default: 5MB)
     * @param callable|null $progressCallback Progress callback function
     * @return ConversionJob
     * @throws ApiException
     * @throws ValidationException
     */
    public function uploadLargeFile(
        string $filePath,
        string $targetFormat,
        array $options = [],
        ?int $chunkSize = null,
        ?callable $progressCallback = null
    ): ConversionJob {
        if (! file_exists($filePath)) {
            throw new ValidationException("File not found: {$filePath}");
        }

        $chunkSize = $chunkSize ?? self::DEFAULT_CHUNK_SIZE;
        $fileSize = filesize($filePath);
        $totalChunks = (int) ceil($fileSize / $chunkSize);
        $filename = basename($filePath);

        // Initialize upload session
        $session = $this->initSession(
            $filename,
            $fileSize,
            $totalChunks,
            $targetFormat,
            $options
        );

        // Upload chunks
        $handle = fopen($filePath, 'rb');
        if (! $handle) {
            throw new ValidationException("Cannot open file for reading: {$filePath}");
        }

        try {
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkData = fread($handle, $chunkSize);
                if ($chunkData === false) {
                    throw new ApiException("Failed to read chunk {$i} from file");
                }

                $this->uploadChunk($session->getSessionId(), $i, $chunkData);

                // Call progress callback if provided
                if ($progressCallback) {
                    $progress = (($i + 1) / $totalChunks) * 100;
                    $progressCallback($i + 1, $totalChunks, $progress);
                }
            }
        } finally {
            fclose($handle);
        }

        // Complete upload and start conversion
        return $this->complete($session->getSessionId());
    }

    /**
     * Calculate optimal chunk size based on file size
     *
     * @param int $fileSize File size in bytes
     * @return int Chunk size in bytes
     */
    public function calculateOptimalChunkSize(int $fileSize): int
    {
        if ($fileSize <= 10 * 1024 * 1024) { // <= 10MB
            return 1 * 1024 * 1024; // 1MB chunks
        } elseif ($fileSize <= 100 * 1024 * 1024) { // <= 100MB
            return 5 * 1024 * 1024; // 5MB chunks
        } elseif ($fileSize <= 500 * 1024 * 1024) { // <= 500MB
            return 10 * 1024 * 1024; // 10MB chunks
        } else {
            return 25 * 1024 * 1024; // 25MB chunks
        }
    }
}
