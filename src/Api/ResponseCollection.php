<?php


namespace WeAreAwesome\FrisbeePHPAPI\Api;


class ResponseCollection
{
    /**
     * @var array
     */
    protected array $responses;


    /**
     * ResponseCollection constructor.
     */
    public function __construct(array $responses = [])
    {

        $this->responses = $responses;
    }

    public function get($key)
    {
        return $this->responses[$key];
    }

    /**
     * @param array $responses
     * @return static
     */
    public static function make(array $responses = []): ResponseCollection
    {
        return new static($responses);
    }
}
