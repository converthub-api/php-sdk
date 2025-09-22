<?php

declare(strict_types=1);

namespace ConvertHub\Resources;

use ConvertHub\ConvertHubClient;
use ConvertHub\Exceptions\ApiException;
use ConvertHub\Models\ConversionJob;
use ConvertHub\Models\DownloadInfo;

/**
 * Resource for handling job operations
 */
class JobResource
{
    /**
     * @var ConvertHubClient
     */
    protected ConvertHubClient $client;

    /**
     * Create a new job resource instance
     *
     * @param ConvertHubClient $client
     */
    public function __construct(ConvertHubClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get job status
     *
     * @param string $jobId Job ID
     * @return ConversionJob
     * @throws ApiException
     */
    public function getStatus(string $jobId): ConversionJob
    {
        $response = $this->client->request('GET', "jobs/{$jobId}");

        return new ConversionJob($response);
    }

    /**
     * Cancel a job
     *
     * @param string $jobId Job ID
     * @return array
     * @throws ApiException
     */
    public function cancel(string $jobId): array
    {
        return $this->client->request('DELETE', "jobs/{$jobId}");
    }

    /**
     * Get download URL for completed conversion
     *
     * @param string $jobId Job ID
     * @return DownloadInfo
     * @throws ApiException
     */
    public function getDownloadUrl(string $jobId): DownloadInfo
    {
        $response = $this->client->request('GET', "jobs/{$jobId}/download");

        return new DownloadInfo($response);
    }

    /**
     * Delete a conversion file
     *
     * @param string $jobId Job ID
     * @return array
     * @throws ApiException
     */
    public function delete(string $jobId): array
    {
        return $this->client->request('DELETE', "jobs/{$jobId}/destroy");
    }

    /**
     * Wait for job completion with polling
     *
     * @param string $jobId Job ID
     * @param int $pollingInterval Polling interval in seconds (default: 2)
     * @param int $maxWaitTime Maximum wait time in seconds (default: 300)
     * @return ConversionJob
     * @throws ApiException
     * @throws \Exception If timeout is reached
     */
    public function waitForCompletion(
        string $jobId,
        int $pollingInterval = 2,
        int $maxWaitTime = 300
    ): ConversionJob {
        $startTime = time();

        while (true) {
            $job = $this->getStatus($jobId);

            if ($job->isCompleted() || $job->isFailed()) {
                return $job;
            }

            if (time() - $startTime > $maxWaitTime) {
                throw new \Exception("Timeout waiting for job completion (job ID: {$jobId})");
            }

            sleep($pollingInterval);
        }
    }
}
