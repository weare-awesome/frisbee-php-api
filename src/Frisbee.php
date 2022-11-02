<?php

namespace WeAreAwesome\FrisbeePHPAPI;

use GuzzleHttp\Client;

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
    public function __construct(Client $client, string $readAPIUrl)
    {
        $this->client = $client;
        $this->readAPIUrl = $readAPIUrl;
    }

    /**
     * @return void
     */
    public function read()
    {

    }


    /**
     * @param Client $client
     * @param $readAPIUrl
     * @return Frisbee
     */
    public static function make(Client $client, $readAPIUrl): Frisbee
    {
        return new static($client, $readAPIUrl);
    }


}