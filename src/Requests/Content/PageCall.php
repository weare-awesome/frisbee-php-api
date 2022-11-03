<?php

namespace WeAreAwesome\FrisbeePHPAPI\Requests\Content;

use GuzzleHttp\Psr7\Response;
use WeAreAwesome\FrisbeePHPAPI\Content\ContentResource;

class PageCall implements ContentCall
{

    public function setDistributionId(int $distributionId)
    {
        // TODO: Implement setDistributionId() method.
    }

    public function getKey(): string
    {
        // TODO: Implement getKey() method.
    }

    public function getMethod(): string
    {
        // TODO: Implement getMethod() method.
    }

    public function getPath(): string
    {
        // TODO: Implement getPath() method.
    }

    public function getOptions(): array
    {
        // TODO: Implement getOptions() method.
    }

    public function setResponse(Response $response)
    {
        // TODO: Implement setResponse() method.
    }

    public function toContentResource(): ContentResource
    {
        // TODO: Implement toContentResource() method.
    }
}