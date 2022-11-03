<?php


namespace WeAreAwesome\FrisbeePHPAPI\Content\Menus;


use Illuminate\Support\Collection;

interface MenuInterface
{
    /**
     * @return Collection
     */
    public function all() : Collection;
}
