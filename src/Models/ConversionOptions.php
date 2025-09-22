<?php

declare(strict_types=1);

namespace ConvertHub\Models;

/**
 * Builder for conversion options
 */
class ConversionOptions
{
    /**
     * @var array Options array
     */
    protected array $options = [];

    /**
     * Set output filename
     *
     * @param string $filename
     * @return $this
     */
    public function setOutputFilename(string $filename): self
    {
        $this->options['output_filename'] = $filename;

        return $this;
    }

    /**
     * Set webhook URL
     *
     * @param string $url
     * @return $this
     */
    public function setWebhookUrl(string $url): self
    {
        $this->options['webhook_url'] = $url;

        return $this;
    }

    /**
     * Set quality (for lossy formats)
     *
     * @param int $quality Quality value (1-100)
     * @return $this
     */
    public function setQuality(int $quality): self
    {
        if ($quality < 1 || $quality > 100) {
            throw new \InvalidArgumentException('Quality must be between 1 and 100');
        }
        $this->options['options']['quality'] = $quality;

        return $this;
    }

    /**
     * Set resolution (for image/video conversions)
     *
     * @param string $resolution Resolution (e.g., "1920x1080")
     * @return $this
     */
    public function setResolution(string $resolution): self
    {
        $this->options['options']['resolution'] = $resolution;

        return $this;
    }

    /**
     * Set bitrate (for audio/video conversions)
     *
     * @param string $bitrate Bitrate (e.g., "320k", "2000k")
     * @return $this
     */
    public function setBitrate(string $bitrate): self
    {
        $this->options['options']['bitrate'] = $bitrate;

        return $this;
    }

    /**
     * Set sample rate (for audio conversions)
     *
     * @param int $sampleRate Sample rate (e.g., 44100, 48000)
     * @return $this
     */
    public function setSampleRate(int $sampleRate): self
    {
        $this->options['options']['sample_rate'] = $sampleRate;

        return $this;
    }

    /**
     * Add metadata
     *
     * @param string $key Metadata key
     * @param mixed $value Metadata value
     * @return $this
     */
    public function addMetadata(string $key, $value): self
    {
        if (! isset($this->options['metadata'])) {
            $this->options['metadata'] = [];
        }
        $this->options['metadata'][$key] = $value;

        return $this;
    }

    /**
     * Set all metadata
     *
     * @param array $metadata
     * @return $this
     */
    public function setMetadata(array $metadata): self
    {
        $this->options['metadata'] = $metadata;

        return $this;
    }

    /**
     * Add custom option
     *
     * @param string $key Option key
     * @param mixed $value Option value
     * @return $this
     */
    public function setOption(string $key, $value): self
    {
        if (! isset($this->options['options'])) {
            $this->options['options'] = [];
        }
        $this->options['options'][$key] = $value;

        return $this;
    }

    /**
     * Get built options array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->options;
    }
}
