<?php

namespace WeAreAwesome\FrisbeePHPAPI\Requests\Content;


class ContentCallCollection
{

    public array $calls =  [];

    public function addCall(ContentCall $call)
    {
        $this->calls[] = $call;
    }

}