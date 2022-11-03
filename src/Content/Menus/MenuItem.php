<?php


namespace WeAreAwesome\FrisbeePHPAPI\Content\Menus;


use Illuminate\Support\Collection;

class MenuItem implements MenuItemInterface
{
    /**
     * @var string|null
     */
    protected ?string $title;
    /**
     * @var string|null
     */
    protected ?string $slug;
    /**
     * @var string|null
     */
    protected ?string $url;
    /**
     * @var array|null
     */
    protected ?array $children;


    /**
     * MenuItem constructor.
     */
    public function __construct(?string $title, ?string $slug, ?string $url, ?array $children)
    {
        $this->title = $title;
        $this->slug = $slug;
        $this->url = $url;
        $this->children = $children;
    }

    /**
     * @return string
     */
    public function title() : string
    {
        return !is_null($this->title) ? $this->title : '';
    }


    /**
     * @return string
     */
    public function url() : string
    {
        if($this->url !== null) {
            return  $this->url;
        }

        if($this->slug !== null) {
            return $this->slug;
        }

        return '';
    }


    /**
     * @return Collection
     */
    public function children() : Collection
    {
        return Collection::make($this->children)->map(function ($item) {
            return MenuItem::make($item);
        });
    }


    /**
     * @return bool
     */
    public function hasChildren() : bool
    {
        if($this->children === null) {
            return false;
        }

        return $this->children()->count() >= 1;
    }



    /**
     * @param array $attributes
     * @return static
     */
    public static function make(array $attributes): MenuItem
    {
        return new static(
            array_key_exists('title', $attributes) ? $attributes['title'] : null,
            array_key_exists('slug', $attributes) ? $attributes['slug'] : null,
            array_key_exists('url', $attributes) ? $attributes['url'] : null,
            array_key_exists('children', $attributes) ? $attributes['children'] : null,

        );
    }

}
