<?php


namespace WeAreAwesome\FrisbeePHPAPI\Content\Menus;

use Illuminate\Support\Collection;

class Menu implements MenuInterface
{
    /**
     * @var int
     */
    protected int $id;
    /**
     * @var string
     */
    protected string $name;
    /**
     * @var array
     */
    protected array $config;
    /**
     * @var array
     */
    protected array $distribution;


    /**
     * Menu constructor.
     */
    public function __construct(int $id, string $name, array $config, array $distribution)
    {
        $this->id = $id;
        $this->name = $name;
        $this->config = $config;
        $this->distribution = $distribution;
    }


    /**
     * @return Collection
     */
    public function all() : Collection
    {
        return Collection::make($this->config)->map(function ($item) {
            return MenuItem::make($item);
        });
    }


    /**
     * @param array $attributes
     * @return Menu
     */
    public static function make(array $attributes) : Menu
    {
        return new static(
            $attributes['id'],
            $attributes['name'],
            is_array($attributes['config']) ? $attributes['config'] : [],
            $attributes['distribution']
        );
    }

}
