<?php

namespace WeAreAwesome\FrisbeePHPAPI\Requests\Content;

use GuzzleHttp\Psr7\Response;
use WeAreAwesome\FrisbeePHPAPI\Content\ContentResource;

interface ContentCall
{


    /**
     * @param int $distributionId
     * @return void
     */
    public function setDistributionId(int $distributionId);

    /**
     * @return string
     */
    public function getKey(): string;

    /**
     * @return string
     */
    public function getMethod(): string;

    /**
     * @return string
     */
    public function getPath(): string;

    /**
     * @return array
     */
    public function getOptions(): array;


    /**
     * @param Response $response
     * @return void
     */
    public function setResponse(Response $response);


    /**
     * @return ContentResource
     */
    public function toContentResource() : ContentResource;

}