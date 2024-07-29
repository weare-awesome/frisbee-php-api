<?php

namespace WeAreAwesome\FrisbeePHPAPI\Requests\Content;

use GuzzleHttp\Psr7\Response;
use WeAreAwesome\FrisbeePHPAPI\Content\ContentResource;
use WeAreAwesome\FrisbeePHPAPI\FrisbeeException;
use WeAreAwesome\FrisbeePHPAPI\Content\Page;

class PageCall implements ContentCall
{
    /**
     * @var
     */
    protected $path;
    /**
     * @var
     */
    protected $distributionId;

    /**
     * @var Response
     */
    protected $response = null;

    /**
     * PageCall constructor.
     */
    public function __construct($path, $distributionId)
    {
        $this->path = $path;
        $this->distributionId = $distributionId;
    }

    /**
     * @param $path
     * @param $distributionId
     * @return static
     */
    public static function make($path, $distributionId)
    {
        return new static($path, $distributionId);
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return 'get';
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return '/page';
    }

    /**
     * @return array[]
     */
    public function getOptions(): array
    {
        return [
            'query' => [
                'path' => $this->path,
                'distribution_id' => $this->distributionId
            ]
        ];
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'page';
    }

    /**
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return ContentResource
     * @throws SeamSystemException
     */
    public function toContentResource(): ContentResource
    {
        if(!$this->response instanceof Response) {
            throw new FrisbeeException('Called before request handled');
        }
        

        if($this->response->getStatusCode() !== 200) {
            throw new FrisbeeException('Content Call error');
        }

        $attributes = json_decode($this->response->getBody()->getContents(), true);

        return Page::make($attributes);

    }

    /**
     * @param int $distributionId
     */
    public function setDistributionId(int $distributionId)
    {
        $this->distributionId = $distributionId;
    }
}
