<?php

namespace Simgroep\ConcurrentSpiderBundle;

use PhpAmqpLib\Message\AMQPMessage;

class CrawlJob
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var array
     */
    private $blacklist;

    /**
     * @var array
     */
    private $metadata;

    /**
     * Constrcutor.
     *
     * @var string $url
     * @var string $baseUrl
     * @var array  $blacklist
     * @var array  $metadata
     */
    public function __construct($url, $baseUrl, array $blacklist = [], array $metadata = [])
    {
        $this->url = $url;
        $this->baseUrl = $baseUrl;
        $this->blacklist = $blacklist;
        $this->metadata = $metadata;
    }

    /**
     * Factory method for creating a job.
     *
     * @param \PhpAmqpLib\Message\AMQPMessage $message
     */
    public static function create(AMQPMessage $message)
    {
        $data = json_decode($message->body, true);

        $urlToCrawl = $data['url'];
        $baseUrl = $data['base_url'];
        $blacklist = $data['blacklist'];
        $metadata = $data['metadata'];

        return new static($urlToCrawl, $baseUrl, $blacklist, $metadata);
    }

    /**
     * Returns an array representation of this object.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'url' => $this->url,
            'base_url' => $this->baseUrl,
            'blacklist' => $this->blacklist,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Returns the URL of this job.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Returns the base url of this job.
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Returns the metadata that belongs to this job.
     *
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Returns the blacklist that belongs to this job.
     *
     * @return array
     */
    public function getBlacklist()
    {
        return $this->blacklist;
    }
}
