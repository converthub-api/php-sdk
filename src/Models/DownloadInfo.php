<?php

declare(strict_types=1);

namespace ConvertHub\Models;

/**
 * Represents download information for a completed conversion
 */
class DownloadInfo
{
    /**
     * @var array Raw response data
     */
    protected array $data;

    /**
     * Create a new download info instance
     *
     * @param array $data Response data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get download URL
     *
     * @return string|null
     */
    public function getDownloadUrl(): ?string
    {
        return $this->data['download_url'] ?? null;
    }

    /**
     * Get filename
     *
     * @return string|null
     */
    public function getFilename(): ?string
    {
        return $this->data['filename'] ?? null;
    }

    /**
     * Get file format
     *
     * @return string|null
     */
    public function getFormat(): ?string
    {
        return $this->data['format'] ?? null;
    }

    /**
     * Get file size in bytes
     *
     * @return int|null
     */
    public function getFileSize(): ?int
    {
        return $this->data['file_size'] ?? null;
    }

    /**
     * Get file size in human-readable format
     *
     * @return string
     */
    public function getFileSizeFormatted(): string
    {
        $size = $this->getFileSize();
        if ($size === null) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Get expiry time for download URL
     *
     * @return string|null
     */
    public function getExpiresAt(): ?string
    {
        return $this->data['expires_at'] ?? null;
    }

    /**
     * Download the file to a local path
     *
     * @param string $destinationPath Path to save the file
     * @return bool True on success
     */
    public function downloadTo(string $destinationPath): bool
    {
        $url = $this->getDownloadUrl();
        if (! $url) {
            return false;
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 300, // 5 minutes timeout
            ],
        ]);

        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            return false;
        }

        return file_put_contents($destinationPath, $content) !== false;
    }

    /**
     * Get raw response data
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
