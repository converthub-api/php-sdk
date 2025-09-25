<?php

declare(strict_types=1);

namespace ConvertHub;

use ConvertHub\Exceptions\ApiException;
use ConvertHub\Exceptions\AuthenticationException;
use ConvertHub\Exceptions\ConvertHubException;
use ConvertHub\Exceptions\ValidationException;
use ConvertHub\Resources\ChunkedUploadResource;
use ConvertHub\Resources\ConversionResource;
use ConvertHub\Resources\FormatResource;
use ConvertHub\Resources\JobResource;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;

/**
 * ConvertHub API Client
 *
 * Main client for interacting with ConvertHub API v2
 *
 * @package ConvertHub
 */
class ConvertHubClient
{
    /**
     * API Version
     */
    public const API_VERSION = 'v2';

    /**
     * Default API Base URL
     */
    public const DEFAULT_BASE_URL = 'https://api.converthub.com';

    /**
     * SDK Version
     */
    public const SDK_VERSION = '1.0.0';

    /**
     * @var Client GuzzleHTTP client instance
     */
    protected Client $httpClient;

    /**
     * @var string API key for authentication
     */
    protected string $apiKey;

    /**
     * @var string Base URL for API requests
     */
    protected string $baseUrl;

    /**
     * @var array Default options for HTTP client
     */
    protected array $defaultOptions = [];

    /**
     * @var ConversionResource Resource for conversion operations
     */
    protected ConversionResource $conversion;

    /**
     * @var JobResource Resource for job operations
     */
    protected JobResource $job;

    /**
     * @var FormatResource Resource for format operations
     */
    protected FormatResource $format;

    /**
     * @var ChunkedUploadResource Resource for chunked upload operations
     */
    protected ChunkedUploadResource $chunkedUpload;

    /**
     * Create a new ConvertHub client instance
     *
     * @param string $apiKey Your ConvertHub API key
     * @param array $options Optional configuration options
     * @throws ConvertHubException If API key is empty
     */
    public function __construct(string $apiKey, array $options = [])
    {
        if (empty($apiKey)) {
            throw new ConvertHubException('API key is required');
        }

        $this->apiKey = $apiKey;
        $this->baseUrl = $options['base_url'] ?? self::DEFAULT_BASE_URL;

        // Configure default options
        $this->defaultOptions = array_merge([
            'timeout' => $options['timeout'] ?? 30,
            'connect_timeout' => $options['connect_timeout'] ?? 10,
            'http_errors' => false,
            'headers' => [
                'User-Agent' => 'ConvertHub-PHP-SDK/' . self::SDK_VERSION,
                'Accept' => 'application/json',
            ],
        ], $options['http_options'] ?? []);

        // Initialize HTTP client with retry middleware
        $this->initializeHttpClient($options);

        // Initialize resources
        $this->initializeResources();
    }

    /**
     * Initialize HTTP client with middleware
     *
     * @param array $options Configuration options
     */
    protected function initializeHttpClient(array $options): void
    {
        $handlerStack = HandlerStack::create();

        // Add authentication middleware
        $handlerStack->push(Middleware::mapRequest(function (RequestInterface $request) {
            return $request->withHeader('Authorization', 'Bearer ' . $this->apiKey);
        }));

        // Add retry middleware if enabled
        if ($options['retry_enabled'] ?? true) {
            $handlerStack->push(Middleware::retry(
                $this->retryDecider($options['max_retries'] ?? 3),
                $this->retryDelay()
            ));
        }

        $this->httpClient = new Client(array_merge($this->defaultOptions, [
            'base_uri' => rtrim($this->baseUrl, '/') . '/' . self::API_VERSION . '/',
            'handler' => $handlerStack,
        ]));
    }

    /**
     * Initialize resource instances
     */
    protected function initializeResources(): void
    {
        $this->conversion = new ConversionResource($this);
        $this->job = new JobResource($this);
        $this->format = new FormatResource($this);
        $this->chunkedUpload = new ChunkedUploadResource($this);
    }

    /**
     * Retry decider for HTTP requests
     *
     * @param int $maxRetries Maximum number of retries
     * @return callable
     */
    protected function retryDecider(int $maxRetries): callable
    {
        return function (
            int $retries,
            RequestInterface $request,
            $response = null,
            $exception = null
        ) use ($maxRetries) {
            // Don't retry if we've exceeded max retries
            if ($retries >= $maxRetries) {
                return false;
            }

            // Retry on connection exceptions
            if ($exception instanceof RequestException) {
                return true;
            }

            // Retry on 5xx errors and rate limiting (429)
            if ($response) {
                $statusCode = $response->getStatusCode();

                return $statusCode >= 500 || $statusCode === 429;
            }

            return false;
        };
    }

    /**
     * Calculate retry delay
     *
     * @return callable
     */
    protected function retryDelay(): callable
    {
        return function (int $retries) {
            return 1000 * $retries; // Exponential backoff
        };
    }

    /**
     * Make an HTTP request to the API
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $options Request options
     * @return array Response data
     * @throws ApiException
     * @throws AuthenticationException
     * @throws ValidationException
     */
    public function request(string $method, string $endpoint, array $options = []): array
    {
        try {
            $response = $this->httpClient->request($method, $endpoint, $options);
            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();

            // Parse JSON response
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ApiException('Invalid JSON response: ' . json_last_error_msg());
            }

            // Handle error responses
            if ($statusCode >= 400) {
                $this->handleErrorResponse($statusCode, $data);
            }

            return $data;

        } catch (GuzzleException $e) {
            throw new ApiException(
                'Request failed: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Handle error responses from API
     *
     * @param int $statusCode HTTP status code
     * @param array $data Response data
     * @throws AuthenticationException
     * @throws ValidationException
     * @throws ApiException
     */
    protected function handleErrorResponse(int $statusCode, array $data): void
    {
        $errorMessage = $data['error']['message'] ?? 'Unknown error';
        $errorCode = $data['error']['code'] ?? 'UNKNOWN_ERROR';
        $errorDetails = $data['error']['details'] ?? null;

        switch ($statusCode) {
            case 401:
            case 403:
                throw new AuthenticationException($errorMessage, $statusCode, null, $errorCode, $errorDetails);

            case 422:
            case 400:
                if ($errorCode === 'VALIDATION_ERROR') {
                    throw new ValidationException($errorMessage, $errorCode, $errorDetails);
                }

                throw new ApiException($errorMessage, $statusCode, null, $errorCode, $errorDetails);

            case 404:
                throw new ApiException($errorMessage, 404, null, $errorCode, $errorDetails);

            case 429:
                $retryAfter = $errorDetails['retry_after'] ?? 60;

                throw new ApiException(
                    $errorMessage . " (retry after {$retryAfter} seconds)",
                    429,
                    null,
                    $errorCode,
                    $errorDetails
                );

            default:
                throw new ApiException($errorMessage, $statusCode, null, $errorCode, $errorDetails);
        }
    }

    /**
     * Get conversion resource
     *
     * @return ConversionResource
     */
    public function conversions(): ConversionResource
    {
        return $this->conversion;
    }

    /**
     * Get job resource
     *
     * @return JobResource
     */
    public function jobs(): JobResource
    {
        return $this->job;
    }

    /**
     * Get format resource
     *
     * @return FormatResource
     */
    public function formats(): FormatResource
    {
        return $this->format;
    }

    /**
     * Get chunked upload resource
     *
     * @return ChunkedUploadResource
     */
    public function chunkedUpload(): ChunkedUploadResource
    {
        return $this->chunkedUpload;
    }

    /**
     * Get account details including membership, credits, and file size limits
     *
     * @return array Account information with credits and plan details
     * @throws ApiException
     * @throws AuthenticationException
     */
    public function getAccount(): array
    {
        return $this->request('GET', 'account');
    }

    /**
     * Get health status of the API
     *
     * @return array Health status data
     * @throws ApiException
     */
    public function health(): array
    {
        return $this->request('GET', 'health');
    }

    /**
     * Get the HTTP client instance
     *
     * @return Client
     */
    public function getHttpClient(): Client
    {
        return $this->httpClient;
    }

    /**
     * Set custom HTTP client
     *
     * @param Client $httpClient
     */
    public function setHttpClient(Client $httpClient): void
    {
        $this->httpClient = $httpClient;
    }
}
