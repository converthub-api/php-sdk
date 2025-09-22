<?php

declare(strict_types=1);

namespace ConvertHub\Models;

/**
 * Represents a conversion job
 */
class ConversionJob
{
    /**
     * @var array Raw response data
     */
    protected array $data;

    /**
     * Create a new conversion job instance
     *
     * @param array $data Response data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get job ID
     *
     * @return string|null
     */
    public function getJobId(): ?string
    {
        return $this->data['job_id'] ?? null;
    }

    /**
     * Get job status
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->data['status'] ?? null;
    }

    /**
     * Check if job is completed
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->getStatus() === 'completed';
    }

    /**
     * Check if job failed
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->getStatus() === 'failed';
    }

    /**
     * Check if job is processing
     *
     * @return bool
     */
    public function isProcessing(): bool
    {
        return in_array($this->getStatus(), ['queued', 'processing']);
    }

    /**
     * Get download URL for completed conversion
     *
     * @return string|null
     */
    public function getDownloadUrl(): ?string
    {
        return $this->data['result']['download_url'] ?? null;
    }

    /**
     * Get file size of converted file
     *
     * @return int|null
     */
    public function getFileSize(): ?int
    {
        return $this->data['result']['file_size'] ?? null;
    }

    /**
     * Get expiry time for download URL
     *
     * @return string|null
     */
    public function getExpiresAt(): ?string
    {
        return $this->data['result']['expires_at'] ?? null;
    }

    /**
     * Get error message if job failed
     *
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return $this->data['error']['message'] ?? null;
    }

    /**
     * Get error code if job failed
     *
     * @return string|null
     */
    public function getErrorCode(): ?string
    {
        return $this->data['error']['code'] ?? null;
    }

    /**
     * Get processing time
     *
     * @return string|null
     */
    public function getProcessingTime(): ?string
    {
        return $this->data['processing_time'] ?? null;
    }

    /**
     * Get metadata
     *
     * @return array|null
     */
    public function getMetadata(): ?array
    {
        return $this->data['metadata'] ?? null;
    }

    /**
     * Get source format
     *
     * @return string|null
     */
    public function getSourceFormat(): ?string
    {
        return $this->data['source_format'] ?? null;
    }

    /**
     * Get target format
     *
     * @return string|null
     */
    public function getTargetFormat(): ?string
    {
        return $this->data['target_format'] ?? null;
    }

    /**
     * Get links
     *
     * @return array|null
     */
    public function getLinks(): ?array
    {
        return $this->data['links'] ?? null;
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

    /**
     * Check if operation was successful
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->data['success'] ?? false;
    }
}
