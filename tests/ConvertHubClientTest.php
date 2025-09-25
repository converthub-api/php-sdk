<?php

declare(strict_types=1);

namespace ConvertHub\Tests;

use ConvertHub\ConvertHubClient;
use ConvertHub\Exceptions\ApiException;
use ConvertHub\Exceptions\AuthenticationException;
use ConvertHub\Exceptions\ConvertHubException;
use ConvertHub\Resources\ChunkedUploadResource;
use ConvertHub\Resources\ConversionResource;
use ConvertHub\Resources\FormatResource;
use ConvertHub\Resources\JobResource;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for ConvertHubClient
 */
class ConvertHubClientTest extends TestCase
{
    /**
     * Test client initialization with valid API key
     */
    public function testClientInitializationWithValidApiKey(): void
    {
        $client = new ConvertHubClient('test-api-key');

        $this->assertInstanceOf(ConvertHubClient::class, $client);
        $this->assertInstanceOf(ConversionResource::class, $client->conversions());
        $this->assertInstanceOf(JobResource::class, $client->jobs());
        $this->assertInstanceOf(FormatResource::class, $client->formats());
        $this->assertInstanceOf(ChunkedUploadResource::class, $client->chunkedUpload());
    }

    /**
     * Test client initialization with empty API key throws exception
     */
    public function testClientInitializationWithEmptyApiKeyThrowsException(): void
    {
        $this->expectException(ConvertHubException::class);
        $this->expectExceptionMessage('API key is required');

        new ConvertHubClient('');
    }

    /**
     * Test successful API request
     */
    public function testSuccessfulApiRequest(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
                'status' => 'healthy',
                'api_version' => 'v2',
            ])),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'https://api.converthub.com/v2/',
            'http_errors' => false,
        ]);

        $client = new ConvertHubClient('test-api-key');
        $client->setHttpClient($httpClient);

        $response = $client->health();

        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertEquals('healthy', $response['status']);
    }

    /**
     * Test authentication error handling
     */
    public function testAuthenticationErrorHandling(): void
    {
        $mockHandler = new MockHandler([
            new Response(401, [], json_encode([
                'success' => false,
                'error' => [
                    'code' => 'AUTHENTICATION_REQUIRED',
                    'message' => 'Authentication is required',
                    'details' => ['hint' => 'Please include a valid Bearer token'],
                ],
            ])),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'https://api.converthub.com/v2/',
            'http_errors' => false,
        ]);

        $client = new ConvertHubClient('test-api-key');
        $client->setHttpClient($httpClient);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Authentication is required');

        $client->health();
    }

    /**
     * Test API exception includes error code and details
     */
    public function testApiExceptionIncludesErrorCodeAndDetails(): void
    {
        $mockHandler = new MockHandler([
            new Response(400, [], json_encode([
                'success' => false,
                'error' => [
                    'code' => 'CONVERSION_NOT_SUPPORTED',
                    'message' => 'Conversion not supported',
                    'details' => [
                        'source_format' => 'xyz',
                        'target_format' => 'abc',
                    ],
                ],
            ])),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'https://api.converthub.com/v2/',
            'http_errors' => false,
        ]);

        $client = new ConvertHubClient('test-api-key');
        $client->setHttpClient($httpClient);

        try {
            $client->health();
            $this->fail('Expected ApiException was not thrown');
        } catch (ApiException $e) {
            $this->assertEquals('CONVERSION_NOT_SUPPORTED', $e->getErrorCode());
            $this->assertIsArray($e->getErrorDetails());
            $this->assertEquals('xyz', $e->getErrorDetails()['source_format']);
        }
    }

    /**
     * Test get account information
     */
    public function testGetAccountInformation(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
                'credits_remaining' => 450,
                'plan' => [
                    'name' => 'Professional',
                    'credits' => 500,
                    'price' => 29.99,
                    'file_size_limit' => 104857600,
                    'file_size_limit_mb' => 100.0,
                ],
            ])),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'https://api.converthub.com/v2/',
            'http_errors' => false,
        ]);

        $client = new ConvertHubClient('test-api-key');
        $client->setHttpClient($httpClient);

        $response = $client->getAccount();

        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertEquals(450, $response['credits_remaining']);
        $this->assertEquals('Professional', $response['plan']['name']);
        $this->assertEquals(500, $response['plan']['credits']);
        $this->assertEquals(100.0, $response['plan']['file_size_limit_mb']);
    }

    /**
     * Test get account with no membership error
     */
    public function testGetAccountNoMembershipError(): void
    {
        $mockHandler = new MockHandler([
            new Response(403, [], json_encode([
                'success' => false,
                'error' => [
                    'code' => 'NO_MEMBERSHIP',
                    'message' => 'No active membership found for your account',
                    'details' => [
                        'user_id' => 12345,
                        'hint' => 'Please subscribe to a plan to use the API',
                    ],
                ],
            ])),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'https://api.converthub.com/v2/',
            'http_errors' => false,
        ]);

        $client = new ConvertHubClient('test-api-key');
        $client->setHttpClient($httpClient);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('No active membership found for your account');
        $this->expectExceptionCode(403);

        $client->getAccount();
    }

    /**
     * Test rate limiting error handling
     */
    public function testRateLimitingErrorHandling(): void
    {
        $mockHandler = new MockHandler([
            new Response(429, [], json_encode([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Too many requests',
                    'details' => ['retry_after' => 30],
                ],
            ])),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'https://api.converthub.com/v2/',
            'http_errors' => false,
        ]);

        $client = new ConvertHubClient('test-api-key');
        $client->setHttpClient($httpClient);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Too many requests (retry after 30 seconds)');
        $this->expectExceptionCode(429);

        $client->health();
    }

    /**
     * Test custom base URL configuration
     */
    public function testCustomBaseUrlConfiguration(): void
    {
        $client = new ConvertHubClient('test-api-key', [
            'base_url' => 'https://custom-api.example.com',
        ]);

        $this->assertInstanceOf(ConvertHubClient::class, $client);
    }

    /**
     * Test custom timeout configuration
     */
    public function testCustomTimeoutConfiguration(): void
    {
        $client = new ConvertHubClient('test-api-key', [
            'timeout' => 60,
            'connect_timeout' => 20,
        ]);

        $this->assertInstanceOf(ConvertHubClient::class, $client);
    }
}
