<?php

declare(strict_types=1);

namespace ConvertHub\Models;

/**
 * Represents a chunked upload session
 */
class ChunkedUploadSession
{
    /**
     * @var array Raw response data
     */
    protected array $data;

    /**
     * Create a new chunked upload session instance
     *
     * @param array $data Response data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get session ID
     *
     * @return string|null
     */
    public function getSessionId(): ?string
    {
        return $this->data['session_id'] ?? null;
    }

    /**
     * Get session expiry time
     *
     * @return string|null
     */
    public function getExpiresAt(): ?string
    {
        return $this->data['expires_at'] ?? null;
    }

    /**
     * Get upload chunk URL template
     *
     * @return string|null
     */
    public function getUploadChunkUrl(): ?string
    {
        return $this->data['links']['upload_chunk'] ?? null;
    }

    /**
     * Get complete upload URL
     *
     * @return string|null
     */
    public function getCompleteUrl(): ?string
    {
        return $this->data['links']['complete'] ?? null;
    }

    /**
     * Get all links
     *
     * @return array|null
     */
    public function getLinks(): ?array
    {
        return $this->data['links'] ?? null;
    }

    /**
     * Check if session is expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        $expiresAt = $this->getExpiresAt();
        if (! $expiresAt) {
            return false;
        }

        $expiryTime = strtotime($expiresAt);

        return $expiryTime !== false && $expiryTime < time();
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
