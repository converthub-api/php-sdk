<?php

declare(strict_types=1);

namespace ConvertHub\Resources;

use ConvertHub\ConvertHubClient;
use ConvertHub\Exceptions\ApiException;

/**
 * Resource for handling format discovery operations
 */
class FormatResource
{
    /**
     * @var ConvertHubClient
     */
    protected ConvertHubClient $client;

    /**
     * Create a new format resource instance
     *
     * @param ConvertHubClient $client
     */
    public function __construct(ConvertHubClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get all supported formats
     *
     * @return array
     * @throws ApiException
     */
    public function all(): array
    {
        return $this->client->request('GET', 'formats');
    }

    /**
     * Get available conversions for a specific format
     *
     * @param string $format Source format
     * @return array
     * @throws ApiException
     */
    public function getConversions(string $format): array
    {
        return $this->client->request('GET', "formats/{$format}/conversions");
    }

    /**
     * Check if a specific conversion is supported
     *
     * @param string $sourceFormat Source format
     * @param string $targetFormat Target format
     * @return array
     * @throws ApiException
     */
    public function checkSupport(string $sourceFormat, string $targetFormat): array
    {
        return $this->client->request('GET', "formats/{$sourceFormat}/to/{$targetFormat}");
    }

    /**
     * Get all supported conversions with detailed mapping
     *
     * @return array
     * @throws ApiException
     */
    public function getSupportedConversions(): array
    {
        return $this->client->request('GET', 'formats/supported-conversions');
    }

    /**
     * Check if a format is supported
     *
     * @param string $format Format to check
     * @return bool
     */
    public function isSupported(string $format): bool
    {
        try {
            $response = $this->getConversions($format);

            return $response['success'] ?? false;
        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * Check if a conversion is supported
     *
     * @param string $sourceFormat Source format
     * @param string $targetFormat Target format
     * @return bool
     */
    public function isConversionSupported(string $sourceFormat, string $targetFormat): bool
    {
        try {
            $response = $this->checkSupport($sourceFormat, $targetFormat);

            return $response['supported'] ?? false;
        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                return false;
            }

            throw $e;
        }
    }
}
