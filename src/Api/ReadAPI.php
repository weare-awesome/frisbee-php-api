<?php

namespace WeAreAwesome\FrisbeePHPAPI\Api;

use GuzzleHttp\Client;
use WeAreAwesome\FrisbeePHPAPI\Content\Page;
use WeAreAwesome\FrisbeePHPAPI\Requests\Content\ContentCallCollection;
use WeAreAwesome\FrisbeePHPAPI\Requests\Content\PageCall;
use WeAreAwesome\FrisbeePHPAPI\Requests\Content\PageRequest;

class ReadAPI extends ApiBase
{


    public function __construct(Client $client, string $readAPIUrl)
    {
        parent::__construct($client, $readAPIUrl);
    }

    public function page(PageRequest|PageCall $request):Page
    {
        if($request instanceof PageRequest === true) {
            return $this->handlePageRequest($request);
        }

        return $this->handlePageCall($request);
    }

    private function handlePageCall(PageCall $call): Page
    {
    }

    private function handlePageRequest(PageRequest $request): Page {

        $pageCall = PageCall::make($request->path, $this->distributionId);
        $callCollection = new ContentCallCollection();
        $callCollection->addCall($pageCall);
        if ($request->hasAdditionalCalls()) {
            foreach ($request->additionCalls as $additionalCall) {
                $additionalCall->setDistributionId($this->distributionId);
                $api->addCall($additionalCall);
            }
        }

        $calls = $api->initCalls();

        $page = $pageCall->toContentResource();


        if ($request->hasAdditionalCalls()) {
            foreach ($calls->except([$pageCall->getKey()]) as $call) {

                $page->additionalContent = $page->additionalContent->add($call->toContentResource());
            }
        }

        return $page;
    }

    public function list()
    {

    }

    /**
     * @param Client $client
     * @param string $readAPIUrl
     * @return ReadAPI
     */
    public static function make(Client $client, string $readAPIUrl): ReadAPI
    {
        return new static($client, $readAPIUrl);
    }



}