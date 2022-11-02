<?php

namespace WeAreAwesome\FrisbeePHPAPI\Api;

use GuzzleHttp\Client;

abstract class ApiBase
{

    /**
     * @var Client
     */
    private Client $client;
    /**
     * @var string
     */
    private string $apiUrl;

    /**
     * @param Client $client
     * @param string $apiUrl
     */
    public function __construct(Client $client, string $apiUrl)
    {
        $this->client = $client;
        $this->apiUrl = $apiUrl;
    }

    public function asyncRequest(string $method, string $path, array $options = []) {
        return $this->client->requestAsync(
            $method,
            $this->fullPath($path),
            array_merge($options, ['headers' => $this->getHeaders()])
        );
    }

    private function fullPath($getPath)
    {
    }

    private function getHeaders()
    {
        return [
            'Content-Type' => 'application/json',
            'Accepts' => 'application/json'
        ];
    }
}