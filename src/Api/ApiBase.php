<?php

namespace WeAreAwesome\FrisbeePHPAPI\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Promise;
use WeAreAwesome\FrisbeePHPAPI\FrisbeeException;
use WeAreAwesome\FrisbeePHPAPI\Requests\Content\ContentCallCollection;
use WeAreAwesome\FrisbeePHPAPI\Requests\Content\Exceptions\FrisbeeContentNotFound;

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
    public function __construct(Client $client, string $apiToken, string $apiUrl)
    {
        $this->client = $client;
        $this->apiUrl = $apiUrl;
        $this->apiToken = $apiToken;
    }

    public function asyncRequest(string $method, string $path, array $options = []) {
        return $this->client->requestAsync(
            $method,
            $this->fullPath($path),
            array_merge($options, ['headers' => $this->getHeaders()])
        );
    }

    public function handleCallCollection(CallCollection $calls)
    {
        $requests = [];
        foreach ($calls->getCalls() as $call) {
            $requests[$call->getKey()] = $this->asyncRequest(
                $call->getMethod(),
                $call->getPath(),
                array_merge($call->getOptions(), ['headers' => $this->getHeaders()])
            );
        }
        try {

            $responses = ResponseCollection::make(Promise\Utils::unwrap($requests));

        }catch (ClientException | ServerException $exception) {
            if ($exception->getResponse()->getStatusCode() === 404 || $exception->getResponse()->getStatusCode() === 422) {
                throw new FrisbeeContentNotFound('We could not find this page or it is not currently distributed');
            }

            throw new FrisbeeException('There has been an error in the system');
        }

        foreach ($calls->getCalls() as $call) {
            $call->setResponse($responses->get($call->getKey()));
        }

        return $calls;

    }

    private function fullPath($getPath)
    {
        return trim($this->apiUrl, '/') . '/' .  trim($getPath,'/');
    }

    private function getHeaders()
    {
        return [
            'Content-Type' => 'application/json',
            'Accepts' => 'application/json',
            'Authorization' => "Bearer " . $this->apiToken
        ];
    }
}
