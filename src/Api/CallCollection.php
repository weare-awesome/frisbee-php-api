<?php


namespace WeAreAwesome\FrisbeePHPAPI\Api;


use WeAreAwesome\FrisbeePHPAPI\Requests\Content\ContentCall;

interface CallCollection
{

    public function getCalls(): array;

    public function addCall(ContentCall $call);
}
