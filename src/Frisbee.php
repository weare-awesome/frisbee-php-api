<?php

namespace WeAreAwesome\FrisbeePHPAPI;

use GuzzleHttp\Client;
use WeAreAwesome\FrisbeePHPAPI\Api\ReadAPI;

/**
 *
 */
class Frisbee
{


    /**
     * @var Client
     */
    private Client $client;

    /**
     * @var string
     */
    private string $readAPIUrl;

    protected ?string $distributionTag = null;


    /**
     * @param Client $client
     * @param string $readAPIUrl
     */
    public function __construct(Client $client, string $apiToken, int $distributionId, string $readAPIUrl)
    {
        $this->client = $client;
        $this->readAPIUrl = $readAPIUrl;
        $this->distributionId = $distributionId;
        $this->apiToken = $apiToken;
    }


    /**
     * @param string $distributionTag
     * @return $this
     */
    public function distributionTagOverride(string $distributionTag): Frisbee
    {
        $this->distributionTag = $distributionTag;
        return $this;
    }

    private function makeRead(): ReadAPI
    {
        return ReadAPI::make($this->client, $this->apiToken, $this->distributionId, $this->readAPIUrl);
    }

    /**
     * @return ReadAPI
     */
    public function read(): ReadAPI
    {

        if($this->distributionTag !== null) {
            return $this->makeRead()->distributionTagOverride($this->distributionTag);
        }

        return $this->makeRead();
    }


    /**
     * @param Client $client
     * @param $readAPIUrl
     * @return Frisbee
     */
    public static function make(Client $client, string $apiToken, int $distributionId, $readAPIUrl): Frisbee
    {
        return new static($client, $apiToken, $distributionId, $readAPIUrl);
    }


}
