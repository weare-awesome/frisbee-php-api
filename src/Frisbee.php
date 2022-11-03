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

    /**
     * @param Client $client
     * @param string $readAPIUrl
     */
    public function __construct(Client $client,int $distributionId, string $readAPIUrl)
    {
        $this->client = $client;
        $this->readAPIUrl = $readAPIUrl;
        $this->distributionId = $distributionId;
    }

    /**
     * @return ReadAPI
     */
    public function read(): ReadAPI
    {
        return ReadAPI::make($this->client, $this->distributionId, $this->readAPIUrl);
    }


    /**
     * @param Client $client
     * @param $readAPIUrl
     * @return Frisbee
     */
    public static function make(Client $client, int $distributionId, $readAPIUrl): Frisbee
    {
        return new static($client, $distributionId, $readAPIUrl);
    }


}
