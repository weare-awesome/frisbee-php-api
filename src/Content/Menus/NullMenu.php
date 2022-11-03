<?php


namespace WeAreAwesome\FrisbeePHPAPI\Content\Menus;

use Illuminate\Support\Collection;

/**
 * Class NullMenu
 * @package App\Lib\Content\Page\Menu
 */
class NullMenu implements MenuInterface
{

    /**
     * @return Collection
     */
    public function all(): Collection
    {
        return Collection::make([]);
    }
}
