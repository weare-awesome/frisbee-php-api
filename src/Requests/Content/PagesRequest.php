<?php

namespace WeAreAwesome\FrisbeePHPAPI\Requests\Content;

class PagesRequest
{

    public function __construct(array $paths = [])
    {
        $this->paths = array_unique($paths);
    }


    public function getPaths()
    {
        return $this->paths;
    }
}
