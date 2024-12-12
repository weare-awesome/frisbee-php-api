<?php

namespace WeAreAwesome\FrisbeePHPAPI\Requests\Content;

use GuzzleHttp\Psr7\Response;
use WeAreAwesome\FrisbeePHPAPI\Content\ContentList;
use WeAreAwesome\FrisbeePHPAPI\Content\ContentResource;
use WeAreAwesome\FrisbeePHPAPI\Content\Page;

class PagesCall implements ContentCall
{
    protected $paths;
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
    public function __construct($paths, $distributionId)
    {
        $this->paths = $paths;
        $this->distributionId = $distributionId;
    }

    /**
     * @param $path
     * @param $distributionId
     * @return static
     */
    public static function make($paths, $distributionId)
    {
        return new static($paths, $distributionId);
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
        return '/pages';
    }

    /**
     * @return array[]
     */
    public function getOptions(): array
    {
        return [
            'query' => [
                'paths' => $this->paths,
                'distribution_id' => $this->distributionId
            ]
        ];
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'pages';
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

        $data = json_decode($this->response->getBody()->getContents(), true);

        return  ContentList::makeFromArray($data['data'], $data['meta']);;

    }

    /**
     * @param int $distributionId
     */
    public function setDistributionId(int $distributionId)
    {
        $this->distributionId = $distributionId;
    }
}
