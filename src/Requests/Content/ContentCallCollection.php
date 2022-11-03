<?php

namespace WeAreAwesome\FrisbeePHPAPI\Requests\Content;


use WeAreAwesome\FrisbeePHPAPI\Api\CallCollection;

class ContentCallCollection implements CallCollection
{

    public array $calls =  [];


    public function getCalls(): array
    {
        return $this->calls;
    }

    public function addCall(ContentCall $call)
    {
        $this->calls[] = $call;
    }

}
