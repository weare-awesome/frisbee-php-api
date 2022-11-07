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

    /**
     * @param array $ignore
     * @return array
     */
    public function except(array $ignore = []) : array
    {
        return array_filter($this->calls, function ($call) use ($ignore) {
            return !in_array($call->getKey(), $ignore);
        });
    }
}
