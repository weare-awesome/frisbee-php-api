<?php

namespace WeAreAwesome\FrisbeePHPAPI\Content;

use Illuminate\Support\Collection;
use WeAreAwesome\FrisbeePHPAPI\Content\ContentResource;

class SiteMap extends ContentResource
{

    /**
     * @var Collection
     */
    protected Collection $data;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = new Collection($data);
    }

    /**
     * @return Collection
     */
    public function getData(): Collection
    {
        return $this->data;
    }


    /**
     * @param array $data
     * @return SiteMap
     */
    public static function make(array $data = []): SiteMap
    {
        return new static($data);
    }

}
