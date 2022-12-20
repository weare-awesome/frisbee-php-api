<?php


namespace WeAreAwesome\FrisbeePHPAPI\Requests\Content;


use GuzzleHttp\Psr7\Response;
use WeAreAwesome\FrisbeePHPAPI\Content\ContentResource;
use WeAreAwesome\FrisbeePHPAPI\Content\DistributionPage;
use WeAreAwesome\FrisbeePHPAPI\Content\Page;

class DistributionCall implements ContentCall
{

    /**
     * @var int
     */
    public $distributionId;


    /**
     * @var Response
     */
    protected $response = null;

    /**
     * @param int $distributionId
     * @return void
     */
    public function setDistributionId(int $distributionId) {
        $this->distributionId = $distributionId;
    }

    /**
     * @return string
     */
    public function getKey(): string {
        return 'distribution';
    }

    /**
     * @return string
     */
    public function getMethod():string {
        return 'get';
    }

    /**
     * @return string
     */
    public function getPath(): string {
        return '/distribution';
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return  [];
    }


    /**
     * @param Response $response
     * @return void
     */
    public function setResponse(Response $response) {
        $this->response = $response;
    }


    /**
     * @return ContentResource
     */
    public function toContentResource() : ContentResource {
        $data = json_decode($this->response->getBody()->getContents(), true);
        $page = DistributionPage::make($data['data']);
        return $page;
    }
}
