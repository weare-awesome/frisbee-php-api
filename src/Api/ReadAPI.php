<?php

namespace WeAreAwesome\FrisbeePHPAPI\Api;

use GuzzleHttp\Client;
use WeAreAwesome\FrisbeePHPAPI\Content\Page;
use WeAreAwesome\FrisbeePHPAPI\Requests\Content\ContentCallCollection;
use WeAreAwesome\FrisbeePHPAPI\Requests\Content\DistributionCall;
use WeAreAwesome\FrisbeePHPAPI\Requests\Content\PageCall;
use WeAreAwesome\FrisbeePHPAPI\Requests\Content\PageRequest;

class ReadAPI extends ApiBase
{

    protected int $distributionId;

    protected ?string $distributionTag = null;

    /**
     * @param Client $client
     * @param string $apiToken
     * @param int $distributionId
     * @param string $readAPIUrl
     */
    public function __construct(Client $client, string $apiToken, int $distributionId, string $readAPIUrl)
    {
        $this->distributionId = $distributionId;
        parent::__construct($client, $apiToken, $readAPIUrl);
    }

    /**
     * @param PageRequest|PageCall $request
     * @return Page
     */
    public function page(PageRequest|PageCall $request):Page
    {
        if($request instanceof PageRequest === true) {
            return $this->handlePageRequest($request);
        }

        return $this->handlePageCall($request);
    }


    /**
     * @param string|null $distributionTagOverride
     * @return $this
     */
    public function distributionTagOverride(string $distributionTagOverride = null): ReadAPI
    {
        $this->distributionTag = $distributionTagOverride;
        return $this;
    }

    /**
     * @param PageCall $call
     * @return Page
     */
    private function handlePageCall(PageCall $call): Page
    {
    }

    /**
     * @param DistributionCall $call
     * @return \WeAreAwesome\FrisbeePHPAPI\Content\ContentResource
     * @throws \WeAreAwesome\FrisbeePHPAPI\Exceptions\FrisbeeAuthorizationException
     * @throws \WeAreAwesome\FrisbeePHPAPI\Exceptions\FrisbeeException
     * @throws \WeAreAwesome\FrisbeePHPAPI\Requests\Content\Exceptions\FrisbeeContentNotFound
     */
    public function distributionCall(DistributionCall $call)
    {
        $callCollection = new ContentCallCollection();
        $call->setDistributionId($this->distributionId);
        $callCollection->addCall($call);
        $this->handleCallCollection($callCollection);
        return $call->toContentResource();
    }

    /**
     * @param PageRequest $request
     * @return Page
     * @throws \WeAreAwesome\FrisbeePHPAPI\Exceptions\FrisbeeAuthorizationException
     * @throws \WeAreAwesome\FrisbeePHPAPI\Exceptions\FrisbeeException
     * @throws \WeAreAwesome\FrisbeePHPAPI\Requests\Content\Exceptions\FrisbeeContentNotFound
     */
    private function handlePageRequest(PageRequest $request): Page {

        $pageCall = PageCall::make($request->path, $this->distributionId);
        $callCollection = new ContentCallCollection();

        $callCollection->addCall($pageCall);

        if ($request->hasAdditionalCalls()) {
            foreach ($request->additionCalls as $additionalCall) {
                $additionalCall->setDistributionId($this->distributionId);
                $callCollection->addCall($additionalCall);
            }
        }
        $this->handleCallCollection($callCollection);


        $page = $pageCall->toContentResource();


        if ($request->hasAdditionalCalls()) {
            foreach ($callCollection->except([$pageCall->getKey()]) as $call) {

                $page->additionalContent = $page->additionalContent->add($call->toContentResource());
            }
        }

        return $page;
    }

    /**
     * @return void
     */
    public function list()
    {

    }

    /**
     * @param Client $client
     * @param int $distributionId
     * @param string $readAPIUrl
     * @return ReadAPI
     */
    public static function make(Client $client, string $apiToken, int $distributionId, string $readAPIUrl): ReadAPI
    {
        return new static($client, $apiToken, $distributionId, $readAPIUrl);
    }



}
